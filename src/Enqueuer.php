<?php namespace Brain\Occipital;

class Enqueuer implements EnqueuerInterface {

    use \Brain\Contextable;

    private $context;

    public function __construct() {
        $this->context = new \ArrayObject;
    }

    public function setup( \Closure $styles_factory, \Closure $scripts_factory ) {
        if ( current_filter() !== 'brain_assets_done' ) {
            return;
        }
        remove_action( current_filter(), [$this, __FUNCTION__ ] );
        $this->setupStyles( $styles_factory );
        $this->setupScripts( $scripts_factory );
        $this->ensureStylesDeps();
        $this->ensureScriptsDeps();
        return TRUE;
    }

    public function enqueue() {
        if ( current_filter() !== 'brain_assets_done' ) {
            return;
        }
        remove_action( current_filter(), [ $this, __FUNCTION__ ] );
        $done = [ ];
        $done[ 'styles_enqueue' ] = $this->enqueueStyles();
        $done[ 'scripts_enqueue' ] = $this->enqueueScripts();
        $done[ 'styles_provide' ] = $this->setProvidedStyles();
        $done[ 'scripts_provide' ] = $this->setProvidedScripts();
        return $done;
    }

    public function getStyles() {
        return $this->getContext( 'context', 'styles' ) ? : [ ];
    }

    public function getScripts() {
        return $this->getContext( 'context', 'scripts' ) ? : [ ];
    }

    public function getProvidedStyles() {
        $provided = $this->getContext( 'context', 'provided_styles' ) ? : [ ];
        return array_values( array_unique( $provided ) );
    }

    public function getProvidedScripts() {
        $provided = $this->getContext( 'context', 'provided_scripts' ) ? : [ ];
        return array_values( array_unique( $provided ) );
    }

    public function getScriptsData() {
        return $this->getContext( 'context', 'scripts_data' ) ? : [ ];
    }

    private function setupStyles( \Closure $styles_factory ) {
        /** @var $styles_iterator \Brain\Occipital\FilterInterface */
        $styles_iterator = $styles_factory->__invoke();
        if ( ! $styles_iterator instanceof \Iterator ) {
            return;
        }
        /** @var $style \Brain\Occipital\StyleInterface */
        foreach ( $styles_iterator as $style ) {
            $styles = $this->getContext( 'context', 'styles' ) ? : [ ];
            $styles[] = $this->getAssetArgs( $style );
            $provided = array_merge(
                (array) $this->getContext( 'context', 'provided_styles' ), $style->getProvided()
            );
            $this->setContext( 'context', 'styles', $styles );
            $this->setContext( 'context', 'provided_styles', $provided );
        };
    }

    private function setupScripts( \Closure $scripts_factory ) {
        /** @var \Brain\Occipital\FilterInterface $scripts_iterator */
        $scripts_iterator = $scripts_factory->__invoke();
        if ( ! $scripts_iterator instanceof \Iterator ) {
            return;
        }
        /** @var $script \Brain\Occipital\ScryptInterface */
        foreach ( $scripts_iterator as $script ) {
            $scripts = $this->getContext( 'context', 'scripts' ) ? : [ ];
            $scripts[] = $this->getAssetArgs( $script );
            $provided = array_merge(
                (array) $this->getContext( 'context', 'provided_scripts' ), $script->getProvided()
            );
            $this->setContext( 'context', 'scripts', $scripts );
            $this->setContext( 'context', 'provided_scripts', $provided );
            $i8n = $script->getLocalizeData();
            if ( is_object( $i8n ) && isset( $i8n->name ) ) {
                $js_object = isset( $i8n->data ) ? (array) $i8n->data : [ ];
                $data = $this->getContext( 'context', 'scripts_data' ) ? : [ ];
                $data[ $script->getHandle() ] = [ $script->getHandle(), $i8n->name, $js_object ];
                $this->setContext( 'context', 'scripts_data', $data );
            }
        };
    }

    private function enqueueScripts() {
        $scripts = $this->getScripts();
        if ( empty( $scripts ) ) {
            return FALSE;
        }
        $data = $this->getScriptsData();
        array_walk( $scripts, function( $args ) use($data) {
            call_user_func_array( 'wp_enqueue_script', $args );
            static $i8n_done;
            if ( is_null( $i8n_done ) ) {
                $i8n_done = TRUE;
                $all_i8n = trim( (string) $this->getContext( 'context', 'i8n_data' ) );
                $GLOBALS[ 'wp_scripts' ]->add_data( $args[ 0 ], 'data', $all_i8n );
            }
            if ( isset( $data[ $args[ 0 ] ] ) ) {
                call_user_func_array( 'wp_localize_script', $data[ $args[ 0 ] ] );
            }
        } );
        return TRUE;
    }

    private function enqueueStyles() {
        $styles = $this->getStyles();
        if ( empty( $styles ) ) {
            return FALSE;
        }
        $last = NULL;
        array_walk( $styles, function( $args ) use(&$last) {
            call_user_func_array( 'wp_enqueue_style', $args );
            $last = $args[ 0 ];
        } );
        $after = array_filter( (array) $this->getContext( 'context', 'after_extra' ) );
        if ( ! empty( $after ) && ! empty( $last ) ) {
            $GLOBALS[ 'wp_styles' ]->add_data( $last, 'after', $after );
        }
        return TRUE;
    }

    private function setProvidedStyles() {
        global $wp_styles;
        $done = $this->getProvidedStyles();
        $registered = FALSE;
        if ( $wp_styles instanceof \WP_Styles && ! empty( $done ) ) {
            $wp_styles->to_do = $this->uniqueFilteredArrays( $wp_styles->to_do, $done, TRUE );
            $wp_styles->done = $this->uniqueFilteredArrays( $wp_styles->done, $done, FALSE );
            $registered = TRUE;
        }
        return $registered;
    }

    private function setProvidedScripts() {
        global $wp_scripts;
        $done = $this->getProvidedScripts();
        $registered = FALSE;
        if ( $wp_scripts instanceof \WP_Scripts && ! empty( $done ) ) {
            $wp_scripts->to_do = $this->uniqueFilteredArrays( $wp_scripts->to_do, $done, TRUE );
            $wp_scripts->done = $this->uniqueFilteredArrays( $wp_scripts->done, $done, FALSE );
            $registered = TRUE;
        }
        return $registered;
    }

    private function ensureStylesDeps() {
        $deps = [ ];
        $provided = $this->getProvidedStyles();
        array_walk( $provided, function( $id ) use(&$deps) {
            if ( wp_style_is( $id, 'registered' ) ) {
                $deps = array_merge( $deps, $GLOBALS[ 'wp_styles' ]->registered[ $id ]->deps );
                $after = $GLOBALS[ 'wp_styles' ]->get_data( $id, 'after' ) ? : [ ];
                $all_after = (array) $this->getContext( 'context', 'after_extra' );
                $this->setContext( 'context', 'after_extra', array_merge( $all_after, $after ) );
            }
        } );
        array_walk( $deps, function($dep) use($provided) {
            if ( ! in_array( $dep, $provided, TRUE ) ) {
                wp_enqueue_style( $dep );
            }
        } );
    }

    private function ensureScriptsDeps() {
        $deps = [ ];
        $provided = $this->getProvidedScripts();
        array_walk( $provided, function( $id ) use(&$deps) {
            if ( wp_script_is( $id, 'registered' ) ) {
                $deps = array_merge( $deps, $GLOBALS[ 'wp_scripts' ]->registered[ $id ]->deps );
                $i8n = $GLOBALS[ 'wp_scripts' ]->get_data( $id, 'data' ) ? : '';
                $all_i8n = (string) $this->getContext( 'context', 'i8n_data' );
                $this->setContext( 'context', 'i8n_data', $all_i8n . $i8n );
            }
        } );
        array_walk( $deps, function($dep)use($provided) {
            if ( ! in_array( $dep, $provided, TRUE ) ) {
                wp_enqueue_script( $dep );
            }
        } );
    }

    private function getAssetArgs( EnqueuableInterface $asset ) {
        $args = [ $asset->getHandle(), $asset->getSrc(), $asset->getDeps(), $asset->getVer() ];
        $args[] = $asset instanceof ScriptInterface ? $asset->isFooter() : $asset->getMedia();
        return $args;
    }

    private function uniqueFilteredArrays( $array1, $array2, $array_diff = FALSE ) {
        $base = ! empty( $array_diff ) ?
            array_diff( (array) $array1, (array) $array2 ) :
            array_merge( (array) $array1, (array) $array2 );
        return array_values( array_filter( array_unique( $base ) ) );
    }

}