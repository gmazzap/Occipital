<?php namespace Brain\Occipital;

class Enqueuer implements EnqueuerInterface {

    private $assets = [ 'style' => [ ], 'script' => [ ] ];
    private $extra_data = [ 'style' => [ ], 'script' => [ ] ];
    private $deps = [ 'style' => [ ], 'script' => [ ] ];
    private $provided_data = [ 'style' => [ ], 'script' => [ ] ];

    public function enqueue( \Closure $assets_factory ) {
        $assets = $assets_factory->__invoke();
        if ( ! $assets instanceof \Iterator ) {
            return FALSE;
        }
        $this->setupGlobals();
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

    public function getScriptsData() {
        return $this->extra_data[ 'script' ];
    }

    public function getStylesData() {
        return $this->extra_data[ 'style' ];
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

    private function setupGlobals() {
        $GLOBALS[ 'wp_styles' ] = isset( $GLOBALS[ 'wp_styles' ] ) ?
            Styles::buildFromWp( $GLOBALS[ 'wp_styles' ] ) :
            new Styles;
        $GLOBALS[ 'wp_scripts' ] = isset( $GLOBALS[ 'wp_scripts' ] ) ?
            Scripts::buildFromWp( $GLOBALS[ 'wp_scripts' ] ) :
            new Scripts;
    }

    private function setupAsset( EnqueuableInterface $asset ) {
        $which = $asset instanceof ScriptInterface ? 'script' : 'style';
        $args = $this->getAssetArgs( $asset );
        if ( ! is_array( $args ) ) {
            return;
        }
        $this->assets[ $which ][] = $args;
        $cb = $which === 'script' ? 'getLocalizeData' : 'getAfter';
        $this->extra_data[ $which ][ $asset->getHandle() ] = call_user_func( [ $asset, $cb ] );
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
            if ( ! in_array( $dep, $provided, TRUE ) ) {
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
                $this->provided_data[ $which ][ $asset_id ], (array) $dep->extra[ $key ]
            );
        }
    }

    private function doEnqueueStyles() {
        $styles = $this->getStyles();
        if ( empty( $styles ) ) {
            return FALSE;
        }
        $data = $this->getStylesData();
        $prov_data = $this->getProvidedStylesData();
        foreach ( array_merge( $this->getStylesDeps(), $styles ) as $args ) {
            call_user_func_array( 'wp_enqueue_style', (array) $args );
            $id = is_string( $args ) ? $args : $args[ 0 ];
            $this->doInlineStyle( $data, $id );
            if ( isset( $prov_data[ $id ] ) ) {
                $GLOBALS[ "wp_styles" ]->add_data( $id, 'after', $prov_data[ $id ] );
            }
        }
        return TRUE;
    }

    private function doEnqueueScript() {
        $scripts = $this->getScripts();
        if ( empty( $scripts ) ) {
            return FALSE;
        }
        $data = $this->getScriptsData();
        $prov_data = $this->getProvidedScriptsData();
        foreach ( array_merge( $this->getScriptsDeps(), $scripts ) as $args ) {
            call_user_func_array( 'wp_enqueue_script', (array) $args );
            $id = is_string( $args ) ? $args : $args[ 0 ];
            $this->doLocalizeScript( $data, $id );
            if ( isset( $prov_data[ $id ] ) ) {
                $GLOBALS[ "wp_scripts" ]->add_data( $id, 'data', implode( '', $prov_data[ $id ] ) );
            }
        }
        return TRUE;
    }

    private function doInlineStyle( $data, $id ) {
        if ( isset( $data[ $id ] ) && is_array( $data[ $id ] ) ) {
            foreach ( (array) $data[ $id ] as $inline_style ) {
                wp_add_inline_style( $id, $inline_style );
            }
        }
    }

    private function doLocalizeScript( $data, $id ) {
        if ( ! isset( $data[ $id ] ) || ! is_array( $data[ $id ] ) ) {
            return;
        }
        foreach ( (array) $data[ $id ] as $i18n_data ) {
            wp_localize_script( $id, $i18n_data->name, (array) $i18n_data->data );
        }
    }

    private function getAssetArgs( EnqueuableInterface $asset ) {
        $handle = $asset->getHandle();
        $src = $asset->getSrc();
        if ( empty( $handle ) || empty( $src ) ) {
            return FALSE;
        }
        $args = [ $handle, $src, $asset->getDeps(), $asset->getVer() ];
        $args[] = $asset instanceof ScriptInterface ? $asset->isFooter() : $asset->getMedia();
        return $args;
    }

}