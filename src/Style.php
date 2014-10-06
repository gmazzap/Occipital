<?php namespace Brain\Occipital;

class Style extends Enqueuable implements StyleInterface {

    private $media;
    private $after = [ ];

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
            $this->after[] = $inline_style;
        }
    }

    public function getAfter() {
        return $this->after;
    }

    public function fillFromRegistered() {
        $handle = $this->getHandle();
        if ( empty( $handle ) ) {
            return;
        }
        if ( ! wp_style_is( $handle, 'registered' ) ) {
            return;
        }
        $dep = $GLOBALS[ 'wp_styles' ]->registered[ $handle ];
        $this->setSrc( $dep->src );
        $this->setDeps( $dep->deps );
        $this->setVer( $dep->ver );
        $this->setMedia( $dep->args );
        if ( isset( $dep->extra[ 'after' ] ) ) {
            foreach ( (array) $dep->extra[ 'after' ] as $after ) {
                $this->setAfter( $after );
            }
        }
    }

}