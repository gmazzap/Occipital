<?php namespace Brain\Lobe;

interface ScriptInterface extends EnqueuableInterface {

    /**
     * Getter for the data to set via `wp_localize_script`
     */
    function getLocalizeData();

    /**
     * Setter for the data to set via `wp_localize_script`.
     * Data must be an object with 2 public properties:
     * "name" for javascript object name and "data" data itself
     *
     * @param object $data Object containing data to set
     */
    function setLocalizeData( $data );

    /**
     * Getter / Setter for "footer" property
     * @param bool $set
     */
    function isFooter( $set = NULL );
}