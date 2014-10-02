<?php

class WP_Scripts {

    public $queue = [ ];
    public $to_do = [ ];
    public $done = [ ];
    public $registered = [ ];
    public $extra = [ ];

    public function get_data( $handle, $key ) {
        $extra = isset( $extra[ $handle ] ) ? $extra[ $handle ] : FALSE;
        return $extra && isset( $extra[ $key ] ) ? $extra[ $key ] : FALSE;
    }

    public function add_data( $handle, $key, $value ) {
        if ( ! isset( $this->extra[ $handle ] ) ) {
            $this->extra[ $handle ] = [ ];
        }
        if ( ! isset( $this->extra[ $handle ][ $key ] ) ) {
            $this->extra[ $handle ][ $key ] = [ ];
        }
        $this->extra[ $handle ][ $key ] = $value;
    }

}