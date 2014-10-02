<?php namespace Brain\Occipital;

class Container implements ContainerInterface {

    private static $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
    private $scripts = [ ];
    private $styles = [ ];
    private $merged_scripts;
    private $merged_styles;
    private $side;

    public function __construct() {
        foreach ( self::$sides as $side ) {
            $this->setStorage( $side );
        }
    }

    public function addScript( ScriptInterface $script, $side = NULL ) {
        return $this->add( $side, $script );
    }

    public function addStyle( StyleInterface $style, $side = NULL ) {
        return $this->add( $side, $style );
    }

    public function removeScript( $script ) {
        $this->remove( $script, 'script' );
    }

    public function removeStyle( $style ) {
        $this->remove( $style, 'style' );
    }

    public function getScripts( $side = NULL ) {
        $this->get( $side, 'scripts' );
    }

    public function getStyles( $side = NULL ) {
        $this->get( $side, 'styles' );
    }

    public function getSide() {
        return $this->side;
    }

    public function setSide( $side ) {
        if ( is_null( $side ) ) {
            $this->side = $side;
        }
    }

    public function getSideStyles() {
        if ( ! is_null( $this->getSide() ) ) {
            return $this->merged_styles;
        }
    }

    public function getSideScripts() {
        if ( ! is_null( $this->getSide() ) ) {
            return $this->merged_scripts;
        }
    }

    public function setSideScripts( \Iterator $scripts ) {
        if ( is_null( $this->merged_scripts ) ) {
            $this->unsetStorage( 'scripts' );
            $this->merged_scripts = $scripts;
        }
    }

    public function setSideStyles( \Iterator $styles ) {
        if ( is_null( $this->merged_styles ) ) {
            $this->unsetStorage( 'styles' );
            $this->merged_styles = $styles;
        }
    }

    private function add( $side, EnqueuableInterface $asset ) {
        $where = $this->checkSide( $side );
        if ( ! $where ) {
            throw new \UnexpectedValueException;
        }
        $assets = $asset instanceof StyleInterface ?
            $this->getStyles( $where ) :
            $this->getScripts( $where );
        $assets[ $asset->getHandle() ] = $asset;
        return $asset;
    }

    private function remove( $asset, $which ) {
        if ( $which === 'style' ) {
            $assets = $this->getSideStyles();
            $cb = 'wp_dequeue_style';
        } else {
            $assets = $this->getSideScripts();
            $cb = 'wp_dequeue_script';
        }
        $handle = $asset instanceof EnqueuableInterface ? $asset->getHandle() : $asset;
        if ( ! is_string( $handle ) ) {
            return;
        }
        if ( $assets->offsetExists( $handle ) ) {
            $assets->offsetUnset( $handle );
        }
        $cb( $handle );
    }

    private function get( $side, $which ) {
        $assets = $this->$which;
        if ( is_null( $side ) ) {
            return $assets;
        }
        if ( isset( $assets[ $side ] ) ) {
            return $assets[ $side ];
        }
        throw new \InvalidArgumentException;
    }

    private function setStorage( $side ) {
        $this->styles[ $side ] = new \ArrayIterator;
        $this->scripts[ $side ] = new \ArrayIterator;
    }

    private function unsetStorage( $which ) {
        $sides = array_diff( [ self::ADMIN, self::FRONT, self::LOGIN ], [ $this->getSide() ] );
        foreach ( $sides as $side ) {
            if ( isset( $this->$which[ $side ] ) ) {
                $this->$which[ $side ] = NULL;
                unset( $this->$which[ $side ] );
            }
        }
    }

    private function checkSide( $side = NULL ) {
        if ( did_action( 'brain_assets_done' ) ) {
            return FALSE;
        }
        $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
        if ( empty( $side ) ) {
            $side = $this->getSide() ? : self::ALL;
        }
        if ( ! in_array( $side, $sides, TRUE ) ) {
            return FALSE;
        }
        if ( $side === self::ALL || ( $side === $this->getSide() || ! did_action( 'brain_assets_ready' ) ) ) {
            return $side;
        }
        return FALSE;
    }

}