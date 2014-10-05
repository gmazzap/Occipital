<?php namespace Brain\Occipital;

class Style extends Enqueuable implements StyleInterface {

    private $media;
    private $after;

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

    public function setAfter( $inline_style = '' ) {
        if ( is_string( $inline_style ) ) {
            $this->after = $inline_style;
        }
    }

    public function getAfter() {
        return $this->after;
    }

}