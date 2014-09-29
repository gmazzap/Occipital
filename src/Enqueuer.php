<?php namespace Brain\Occipital;

class Enqueuer implements EnqueuerInterface {

    private $provided = [ 'script' => [ ], 'styles' => [ ] ];

    public function enqueueScripts( \Closure $scripts_factory ) {
        /** @var \Brain\Occipital\Filter $scripts */
        $scripts = $scripts_factory->__invoke();
        if ( ! $scripts instanceof FilterInterface ) {
            return;
        }
        $provided = [ ];
        /** @type \Brain\Occipital\ScriptInterface $script */
        foreach ( $scripts as $script ) {
            $args = $this->getAssetArgs( $script );
            $provided = array_merge( $provided, $script->getProvide() );
            call_user_func_array( 'wp_enqueue_script', $args );
            $data = $script->getLocalizeData();
            if ( is_object( $data ) && isset( $data->name ) ) {
                $data = isset( $data->data ) ? (array) $data->data : [ ];
                wp_localize_script( $args[ 0 ], $data->name, $data );
            }
        }
        $this->provided[ 'scripts' ] = array_filter( array_unique( array_values( $provided ) ) );
    }

    public function enqueueStyles( \Closure $styles_factory ) {
        /** @var $scripts \Brain\Occipital\Filter */
        $styles = $styles_factory->__invoke();
        if ( ! $styles instanceof FilterInterface ) {
            return;
        }
        $provided = [ ];
        /** @type \Brain\Occipital\StyleInterface $style */
        foreach ( $styles as $style ) {
            $provided = array_merge( $provided, $style->getProvide() );
            call_user_func_array( 'wp_enqueue_style', $this->getAssetArgs( $style ) );
        }
        $this->provided[ 'styles' ] = array_filter( array_unique( array_values( $provided ) ) );
    }

    public function registerProvided() {
        if ( ! doing_action( 'wp_head' ) ) {
            return;
        }
        global $wp_scripts, $wp_styles;
        if ( $wp_scripts instanceof \WP_Scripts ) {
            $wp_scripts->to_do = array_diff( $wp_scripts->to_do, $this->provided[ 'scripts' ] );
            $wp_scripts->done = $this->provided[ 'scripts' ];
        }
        if ( $wp_styles instanceof \WP_Styles ) {
            $wp_styles->to_do = array_diff( $wp_styles->to_do, $this->provided[ 'styles' ] );
            $wp_styles->done = $this->provided[ 'styles' ];
        }
    }

    private function getAssetArgs( EnqueuableInterface $asset ) {
        $args = [ $asset->getHandle(), $asset->getSrc(), $asset->getDeps(), $asset->getVer() ];
        $args[] = $asset instanceof ScriptInterface ? $asset->isFooter() : $asset->getMedia();
        return $args;
    }

}