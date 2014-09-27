<?php namespace Brain\Lobe;

interface StyleInterface extends EnqueuableInterface {

    function getMedia();

    function setMedia( $media );
}