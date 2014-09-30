<?php namespace Brain\Occipital;

class Enqueuer implements EnqueuerInterface {

    private $provided = [ 'script' => [ ], 'styles' => [ ] ];

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
        $done_scripts = $this->getProvided( 'scripts' );
        $done_styles = $this->getProvided( 'styles' );
        if ( $wp_scripts instanceof \WP_Scripts ) {
            $wp_scripts->to_do = array_values( array_diff( $wp_scripts->to_do, $done_scripts ) );
            $wp_scripts->done = $done_scripts;
        }
        if ( $wp_styles instanceof \WP_Styles ) {
            $wp_styles->to_do = array_values( array_diff( $wp_styles->to_do, $done_styles ) );
            $wp_styles->done = $done_styles;
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

}