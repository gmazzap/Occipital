<?php namespace Brain\Occipital;

interface StyleInterface extends EnqueuableInterface {

    /**
     * Getter for the 'media' property of the style.
     */
    function getMedia();

    /**
     * Setter for the 'media' property of the style.
     *
     * @param string $media The media type to set
     */
    function setMedia( $media );

    /**
     * Allow to setup inline style to be printed after the style
     *
     * @param string $inline_style
     */
    function setAfter( $inline_style = '' );

    /**
     * Getter for the 'after' property of the style.
     */
    function getAfter();
}