<?php namespace Brain\Occipital;

class Script extends Enqueuable implements ScriptInterface {

    private $data;
    private $is_footer;

    public function getLocalizeData() {
        return $this->data;
    }

    public function isFooter( $set = NULL ) {
        if ( ! is_null( $set ) ) {
            $this->is_footer = ! empty( $set );
        }
        return is_null( $set ) ? $this->is_footer : $this;
    }

    public function setLocalizeData( stdClass $data ) {
        if ( isset( $data->name ) && isset( $data->data ) && is_string( $data->name ) ) {
            $data->name = filter_var( $data->data, FILTER_SANITIZE_STRING );
            $data->data = (array) $data->data;
            $this->data = $data;
        }
        return $this;
    }

}