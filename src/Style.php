<?php namespace Brain\Occipital;

class Style extends Enqueuable implements StyleInterface {

    private $media;

    public function getMedia() {
        return $this->media ? : 'all';
    }

    public function setMedia( $media ) {
        $ok = [ 'all', 'aural', 'braille', 'handheld', 'projection', 'print', 'screen', 'tty', 'tv' ];
        if ( is_string( $media ) && in_array( strtolower( $media ), $ok, TRUE ) ) {
            $this->media = strtolower( $media );
        }
        return $this;
    }

}