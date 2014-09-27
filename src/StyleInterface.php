<?php namespace Brain\Lobe;

interface StyleInterface extends EnqueuableInterface {

    /**
     * Getter for the 'media' properties of the style.
     */
    function getMedia();

    /**
     * Setter for the 'media' properties of the style.
     *
     * @param string $media The media type to set
     */
    function setMedia( $media );
}