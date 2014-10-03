<?php namespace Brain\Occipital;

class Container implements ContainerInterface {

    private static $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
    private $scripts = [ ];
    private $styles = [ ];
    private $assets_iterator;
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
        return $this->get( $side, 'scripts' );
    }

    public function getStyles( $side = NULL ) {
        return $this->get( $side, 'styles' );
    }

    public function getSide() {
        return $this->side;
    }

    public function setSide( $side ) {
        if ( is_null( $this->side ) ) {
            $this->side = $side;
        }
    }

    public function getAssetsIterator() {
        return $this->assets_iterator;
    }

    public function setAssetsIterator( \Iterator $assets ) {
        if ( is_null( $this->getAssetsIterator() ) ) {
            $this->unsetStorage();
            $this->assets_iterator = $assets;
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
            $assets = $this->getAssetsIterator();
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

    private function unsetStorage() {
        $sides = array_diff( [ self::ADMIN, self::FRONT, self::LOGIN ], [ $this->getSide() ] );
        foreach ( $sides as $side ) {
            if ( isset( $this->styles[ $side ] ) ) {
                $this->styles[ $side ] = NULL;
                unset( $this->styles[ $side ] );
            }
            if ( isset( $this->scripts[ $side ] ) ) {
                $this->scripts[ $side ] = NULL;
                unset( $this->scripts[ $side ] );
            }
        }
    }

    private function checkSide( $side = NULL ) {
        if ( did_action( 'brain_assets_done' ) ) {
            return FALSE;
        }
        if ( empty( $side ) ) {
            $side = $this->getSide() ? : self::ALL;
        }
        if (
            $side === self::ALL
            || ( $side === $this->getSide() || ! did_action( 'brain_assets_ready' ) )
        ) {
            return $side;
        }
        return FALSE;
    }

}