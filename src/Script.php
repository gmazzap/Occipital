<?php namespace Brain\Occipital;

class Script extends Enqueuable implements ScriptInterface {

    private $data = [ ];
    private $is_footer = FALSE;

    public function getLocalizeData() {
        return $this->data;
    }

    public function setLocalizeData( $data ) {
        if ( is_object( $data ) ) {
            $data = get_object_vars( $data );
        }
        if ( isset( $data[ 'name' ] ) && isset( $data[ 'data' ] ) && is_string( $data[ 'name' ] ) ) {
            $data_obj = new \stdClass;
            $data_obj->name = filter_var( $data[ 'name' ], FILTER_SANITIZE_STRING );
            $data_obj->data = (array) $data[ 'data' ];
            $this->data[] = $data_obj;
        }
        return $this;
    }

    public function isFooter( $set = NULL ) {
        if ( ! is_null( $set ) ) {
            $this->is_footer = ! empty( $set );
        }
        return is_null( $set ) ? $this->is_footer : $this;
    }

    public function fillFromRegistered() {
        $handle = $this->getHandle();
        if ( empty( $handle ) || ! wp_script_is( $handle, 'registered' ) ) {
            return;
        }
        $dep = $GLOBALS[ 'wp_scripts' ]->registered[ $handle ];
        $this->setSrc( $dep->src );
        $this->setDeps( $dep->deps );
        $this->setVer( $dep->ver );
        $this->setMedia( $dep->args );
        if ( isset( $dep->extra[ 'group' ] ) ) {
            $this->isFooter(  ! empty( (int) $dep->extra[ 'group' ] ) );
        }
        if ( isset( $dep->extra[ 'data' ] ) ) {
            $this->parseRegisteredData( $dep->extra[ 'data' ] );
        }
    }

    private function parseRegisteredData( $raw ) {
        $m = [ ];
        preg_match_all( "#var ([\w]+) = (.+);#", $raw, $m, NULL );
        if ( ! isset( $m[ 1 ] ) || ! isset( $m[ 2 ] ) ) {
            return;
        }
        foreach ( $m[ 1 ] as $i => $var ) {
            try {
                $data = json_decode( $m[ 2 ][ $i ] );
            } catch ( \Exception $e ) {
                continue;
            }
            $this->setLocalizeData( [ 'name' => $var, 'data' => $data ] );
        }
    }

}