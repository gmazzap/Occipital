<?php namespace Brain\Occipital;

class Enqueuer implements EnqueuerInterface {

    use \Brain\Contextable;

    private $context;

    public function __construct() {
        $this->context = new \ArrayObject;
    }

    public function setup( \Closure $styles_factory, \Closure $scripts_factory ) {
        if ( ! doing_action( 'wp_head' ) ) {
            return;
        }
        $this->setupStyles( $styles_factory );
        $this->setupScripts( $scripts_factory );
        $this->ensureStylesDeps();
        $this->ensureScriptsDeps();
    }

    public function enqueue() {
        $this->enqueueStyles();
        $this->enqueueScripts();
        $this->registerProvidedStyles();
        $this->registerProvidedScripts();
    }

    public function getStyles() {
        return $this->getContext( 'context', 'styles' ) ? : [ ];
    }

    public function getScripts() {
        return $this->getContext( 'context', 'scripts' ) ? : [ ];
    }

    public function getProvidedStyles() {
        return $this->getContext( 'context', 'provided_styles' ) ? : [ ];
    }

    public function getProvidedScripts() {
        return $this->getContext( 'context', 'provided_scripts' ) ? : [ ];
    }

    public function getScriptsData() {
        return $this->getContext( 'context', 'scripts_data' ) ? : [ ];
    }

    private function enqueueScripts() {
        $scripts = $this->getScripts();
        if ( empty( $scripts ) ) {
            return;
        }
        array_walk( $scripts, function( $args ) {
            call_user_func_array( 'wp_enqueue_script', $args );
            $data = $this->getScriptsData();
            if ( isset( $data[ $args[ 0 ] ] ) ) {
                call_user_func_array( 'wp_localize_script', $data[ $args[ 0 ] ] );
            }
        } );
        return TRUE;
    }

    private function enqueueStyles() {
        $styles = $this->getStyles();
        if ( empty( $styles ) ) {
            return;
        }
        array_walk( $styles, function( $args ) {
            call_user_func_array( 'wp_enqueue_style', $args );
        } );
        return TRUE;
    }

    private function registerProvidedStyles() {
        global $wp_styles;
        $done_styles = $this->getProvidedStyles();
        if ( $wp_styles instanceof \WP_Styles && ! empty( $done_styles ) ) {
            $wp_styles->to_do = array_values( array_diff( $wp_styles->to_do, $done_styles ) );
            $wp_styles->done = $done_styles;
        }
    }

    private function registerProvidedScripts() {
        global $wp_scripts;
        $done_scripts = $this->getProvidedScripts();
        if ( $wp_scripts instanceof \WP_Scripts && ! empty( $done_scripts ) ) {
            $wp_scripts->to_do = array_values( array_diff( $wp_scripts->to_do, $done_scripts ) );
            $wp_scripts->done = $done_scripts;
        }
        return TRUE;
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

    private function getAssetArgs( EnqueuableInterface $asset ) {
        $args = [ $asset->getHandle(), $asset->getSrc(), $asset->getDeps(), $asset->getVer() ];
        $args[] = $asset instanceof ScriptInterface ? $asset->isFooter() : $asset->getMedia();
        return $args;
    }

    private function ensureStylesDeps() {
        $deps = [ ];
        $provided = $this->getProvidedStyles();
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

    private function ensureScriptsDeps() {
        $deps = [ ];
        $provided = $this->getProvidedScripts();
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