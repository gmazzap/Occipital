<?php namespace Brain\Occipital;

class Script extends Enqueuable implements ScriptInterface {

    private $data;
    private $is_footer;

    public function getLocalizeData() {
        return $this->data;
    }

    public function setLocalizeData( Array $data ) {
        if ( isset( $data[ 'name' ] ) && isset( $data[ 'data' ] ) && is_string( $data[ 'name' ] ) ) {
            $this->data = new \stdClass;
            $this->data->name = filter_var( $data[ 'name' ], FILTER_SANITIZE_STRING );
            $this->data->data = (array) $data[ 'data' ];
        }
        return $this;
    }

    public function isFooter( $set = NULL ) {
        if ( ! is_null( $set ) ) {
            $this->is_footer = ! empty( $set );
        }
        return is_null( $set ) ? $this->is_footer : $this;
    }

}