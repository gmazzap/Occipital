<?php namespace Brain\Lobe;

interface ScriptInterface extends EnqueuableInterface {

    function getLocalizeData();

    /**
     * @param object $data 2 properties 'name' for object name and 'data' for localization data
     */
    function setLocalizeData( stdClass $data );

    function isFooter( $set = NULL );
}