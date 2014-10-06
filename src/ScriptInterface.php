<?php namespace Brain\Occipital;

interface ScriptInterface extends EnqueuableInterface {

    /**
     * Getter for the data to set via `wp_localize_script`
     */
    function getLocalizeData();

    /**
     * Setter for the data to set via `wp_localize_script`.
     * Data must be an array with 2 keys: "name" for javascript object name and "data" data itself
     *
     * @param array|object $data Array or object containing data to set
     */
    function setLocalizeData( $data );

    /**
     * Getter / Setter for "footer" property
     * @param bool $set
     */
    function isFooter( $set = NULL );
}