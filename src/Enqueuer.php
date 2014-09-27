<?php namespace Brain\Lobe;

class Enqueuer implements EnqueuerInterface {

    private $provided = [ 'script' => [ ], 'styles' => [ ] ];

    public function enqueueScripts( \Iterator $scripts ) {
        $provided = [ ];
        /** @type \Brain\Lobe\ScriptInterface $script */
        foreach ( $scripts as $script ) {
            $args = $this->getAssetArgs( $script );
            $provided = array_merge( $provided, $script->getProvide() );
            call_user_func_array( 'wp_enqueue_script', $args );
            $data = $script->getLocalizeData();
            if ( $data instanceof stdClass && isset( $data->name ) && isset( $data->data ) ) {
                wp_localize_script( $args[ 0 ], $data->name, (array) $data->data );
            }
        }
        $this->provided[ 'scripts' ] = array_filter( array_unique( array_values( $provided ) ) );
    }

    public function enqueueStyles( \Iterator $styles ) {
        $provided = [ ];
        /** @type \Brain\Lobe\StyleInterface $style */
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