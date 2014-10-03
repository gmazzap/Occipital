<?php namespace Brain\Occipital;

class Enqueuer implements EnqueuerInterface {

    private $assets = [ 'style' => [ ], 'script' => [ ] ];
    private $deps = [ 'style' => [ ], 'script' => [ ] ];
    private $provided_data = [ 'style' => [ ], 'script' => [ ] ];

    public function enqueue( \Closure $assets_factory ) {
        $assets = $assets_factory->__invoke();
        if ( ! $assets instanceof \Iterator ) {
            return FALSE;
        }
        foreach ( $assets as $asset ) {
            $this->setupAsset( $asset );
        }
        $GLOBALS[ 'wp_styles' ]->done = array_values( array_unique( $GLOBALS[ 'wp_styles' ]->done ) );
        $GLOBALS[ 'wp_scripts' ]->done = array_values( array_unique( $GLOBALS[ 'wp_scripts' ]->done ) );
        return [
            'styles'  => $this->doEnqueueStyles(),
            'scripts' => $this->doEnqueueScript()
        ];
    }

    public function getStyles() {
        return $this->assets[ 'style' ];
    }

    public function getScripts() {
        return $this->assets[ 'script' ];
    }

    public function getStylesDeps() {
        return $this->deps[ 'style' ];
    }

    public function getScriptsDeps() {
        return $this->deps[ 'script' ];
    }

    public function getProvidedStylesData() {
        return $this->provided_data[ 'style' ];
    }

    public function getProvidedScriptsData() {
        return $this->provided_data[ 'script' ];
    }

    private function setupAsset( EnqueuableInterface $asset ) {
        $which = $asset instanceof StyleInterface ? 'style' : 'script';
        $args = $this->getAssetArgs( $asset );
        $this->assets[ $which ][] = $args;
        $provided = $asset->getProvided();
        if ( empty( $provided ) ) {
            return;
        }
        $global = $GLOBALS[ "wp_{$which}s" ];
        $global->to_do = array_diff( (array) $global->to_do, $provided );
        $global->done = array_merge( (array) $global->done, $provided );
        $this->setupAssetProvided( $provided, $which, $args[ 0 ] );
    }

    private function setupAssetProvided( $provided, $which, $asset_id ) {
        $global = $GLOBALS[ "wp_{$which}s" ];
        foreach ( $provided as $handle ) {
            $dep = isset( $global->registered[ $handle ] ) ? $global->registered[ $handle ] : FALSE;
            if ( ! $dep instanceof \_WP_Dependency ) {
                continue;
            }
            $this->setupProvidedDepData( $dep, $which, $asset_id );
            $all_deps = array_merge( [ $handle ], $dep->deps );
            $this->setupProvidedDeps( $all_deps, $provided, $which );
        }
    }

    private function setupProvidedDeps( Array $deps, Array $provided, $which ) {
        foreach ( $deps as $dep ) {
            if ( ! in_array( $dep, $provided ) ) {
                $this->deps[ $which ][] = $dep;
                continue;
            }
            $this->deps[ $which ] = array_diff( $this->deps[ $which ], [ $dep ] );
        }
    }

    private function setupProvidedDepData( \_WP_Dependency $dep, $which, $asset_id ) {
        if ( ! isset( $this->provided_data[ $which ][ $asset_id ] ) ) {
            $this->provided_data[ $which ][ $asset_id ] = [ ];
        }
        $key = $which === 'style' ? 'after' : 'data';
        if ( isset( $dep->extra[ $key ] ) ) {
            $this->provided_data[ $which ][ $asset_id ] = array_merge(
                $this->provided_data[ $which ][ $asset_id ], $dep->extra[ $key ]
            );
        }
    }

    private function doEnqueueStyles() {
        $assets = $this->getStyles();
        if ( empty( $assets ) ) {
            return FALSE;
        }
        $deps = $this->getStylesDeps();
        $cb = 'wp_enqueue_style';
        $data = $this->getProvidedStylesData();
        return $this->doEnqueueAssets( array_merge( $deps, $assets ), $cb, $data, 'style' );
    }

    private function doEnqueueScript() {
        $assets = $this->getScripts();
        if ( empty( $assets ) ) {
            return FALSE;
        }
        $deps = $this->getScriptsDeps();
        $cb = 'wp_enqueue_script';
        $data = implode( '', $this->getProvidedScriptsData() );
        return $this->doEnqueueAssets( array_merge( $deps, $assets ), $cb, $data, 'script' );
    }

    private function doEnqueueAssets( $assets, $cb, $data, $which ) {
        $data_key = $which === 'style' ? 'after' : 'data';
        foreach ( $assets as $args ) {
            call_user_func_array( $cb, (array) $args );
            if ( isset( $data[ $args[ 0 ] ] ) ) {
                $GLOBALS[ "wp_{$which}s" ]->add_data( $args[ 0 ], $data_key, $data[ $args[ 0 ] ] );
            }
        }
        return TRUE;
    }

    private function getAssetArgs( EnqueuableInterface $asset ) {
        $args = [ $asset->getHandle(), $asset->getSrc(), $asset->getDeps(), $asset->getVer() ];
        $args[] = $asset instanceof ScriptInterface ? $asset->isFooter() : $asset->getMedia();
        return $args;
    }

}