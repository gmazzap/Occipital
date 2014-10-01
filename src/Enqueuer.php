<?php namespace Brain\Occipital;

class Enqueuer implements EnqueuerInterface {

    private $provided = [ 'scripts' => [ ], 'styles' => [ ] ];

    public function enqueueScripts( \Closure $scripts_factory ) {
        /** @var \Brain\Occipital\FilterInterface $scripts */
        $scripts = $scripts_factory->__invoke();
        if ( ! $scripts instanceof \Iterator ) {
            return;
        }
        $provided = [ ];
        /** @type \Brain\Occipital\ScriptInterface $script */
        foreach ( $scripts as $script ) {
            $args = $this->getAssetArgs( $script );
            $provided = array_merge( $provided, $script->getProvided() );
            call_user_func_array( 'wp_enqueue_script', $args );
            $data = $script->getLocalizeData();
            if ( is_object( $data ) && isset( $data->name ) ) {
                $js_object = isset( $data->data ) ? (array) $data->data : [ ];
                wp_localize_script( $args[ 0 ], $data->name, $js_object );
            }
        }
        $this->provided[ 'scripts' ] = array_filter( array_unique( array_values( $provided ) ) );
        return TRUE;
    }

    public function enqueueStyles( \Closure $styles_factory ) {
        /** @var $scripts \Brain\Occipital\FilterInterface */
        $styles = $styles_factory->__invoke();
        if ( ! $styles instanceof \Iterator ) {
            return;
        }
        $provided = [ ];
        /** @type \Brain\Occipital\StyleInterface $style */
        foreach ( $styles as $style ) {
            $provided = array_merge( $provided, $style->getProvided() );
            call_user_func_array( 'wp_enqueue_style', $this->getAssetArgs( $style ) );
        }
        $this->provided[ 'styles' ] = array_filter( array_unique( array_values( $provided ) ) );
        return TRUE;
    }

    public function registerProvided() {
        if ( ! doing_action( 'wp_head' ) ) {
            return;
        }
        global $wp_scripts, $wp_styles;
        $done_styles = $this->getProvided( 'styles' );
        if ( $wp_styles instanceof \WP_Styles && ! empty( $done_styles ) ) {
            $this->ensureStylesDeps( $done_styles );
            $wp_styles->to_do = array_values( array_diff( $wp_styles->to_do, $done_styles ) );
            $wp_styles->done = $done_styles;
        }
        $done_scripts = $this->getProvided( 'scripts' );
        if ( $wp_scripts instanceof \WP_Scripts && ! empty( $done_scripts ) ) {
            $this->ensureScriptsDeps( $done_scripts );
            $wp_scripts->to_do = array_values( array_diff( $wp_scripts->to_do, $done_scripts ) );
            $wp_scripts->done = $done_scripts;
        }
        return TRUE;
    }

    public function getProvided( $which = NULL ) {
        $provided = $this->provided;
        if ( is_null( $which ) ) {
            return $provided;
        }
        if ( ! in_array( strtolower( $which ), [ 'scripts', 'styles' ], TRUE ) ) {
            throw new \InvalidArgumentException;
        }
        return strtolower( $which ) === 'scripts' ? $provided[ 'scripts' ] : $provided[ 'styles' ];
    }

    private function getAssetArgs( EnqueuableInterface $asset ) {
        $args = [ $asset->getHandle(), $asset->getSrc(), $asset->getDeps(), $asset->getVer() ];
        $args[] = $asset instanceof ScriptInterface ? $asset->isFooter() : $asset->getMedia();
        return $args;
    }

    private function ensureStylesDeps( Array $provided ) {
        $deps = [ ];
        array_walk( $provided, function( $id ) use(&$deps) {
            if ( wp_style_is( $id, 'registered' ) ) {
                $deps = array_merge( $deps, $GLOBALS[ 'wp_styles' ]->registered[ $id ]->deps );
            }
        } );
        $enqueue = array_filter( array_unique( $deps ), function($id) {
            return wp_style_is( $id, 'registered' ) && ! wp_style_is( $id, 'queue' );
        } );
        array_walk( $enqueue, function($dep) {
            wp_enqueue_style( $dep );
        } );
    }

    private function ensureScriptsDeps( Array $provided ) {
        $deps = [ ];
        array_walk( $provided, function( $id ) use(&$deps) {
            if ( wp_script_is( $id, 'registered' ) ) {
                $deps = array_merge( $deps, $GLOBALS[ 'wp_script' ]->registered[ $id ]->deps );
            }
        } );
        $enqueue = array_filter( array_unique( $deps ), function($id) {
            return wp_script_is( $id, 'registered' ) && ! wp_script_is( $id, 'queue' );
        } );
        array_walk( $enqueue, function($dep) {
            wp_enqueue_script( $dep );
        } );
    }

}