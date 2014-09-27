<?php namespace Brain\Lobe;

class Style extends Enqueuable implements StyleInterface {

    private $media;

    public function getMedia() {
        return $this->media ? : 'all';
    }

    public function setMedia( $media ) {
        $medias = [
            'all',
            'aural',
            'braille',
            'handheld',
            'projection',
            'print',
            'screen',
            'tty',
            'tv'
        ];
        if ( is_string( $media ) && in_array( strtolower( $media ), $medias, TRUE ) ) {
            $this->media = strtolower( $media );
        }
        return $this;
    }

}