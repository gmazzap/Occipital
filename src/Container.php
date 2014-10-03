<?php namespace Brain\Occipital;

class Container implements ContainerInterface {

    private static $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
    private $scripts = [ ];
    private $styles = [ ];
    private $assets_iterator;
    private $side;

    public function __construct() {
        $this->setStorage();
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
        if ( is_null( $this->side ) && in_array( $side, self::$sides, TRUE ) ) {
            $this->side = $side;
        }
    }

    public function getAssetsIterator() {
        return $this->assets_iterator;
    }

    public function setAssetsIterator( \Iterator $assets ) {
        if ( is_null( $this->getAssetsIterator() ) && ! is_null( $this->getSide() ) ) {
            $this->unsetStorage();
            $this->assets_iterator = $assets;
        }
    }

    private function add( $side, EnqueuableInterface $asset ) {
        if ( empty( $side ) ) {
            $side = self::ALL;
        }
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
        $assets = $this->getAssetsIterator();
        $cb = "wp_dequeue_{$which}";
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

    private function setStorage() {
        foreach ( self::$sides as $side ) {
            $this->styles[ $side ] = new \ArrayIterator;
            $this->scripts[ $side ] = new \ArrayIterator;
        }
    }

    private function unsetStorage() {
        $sides = array_diff( self::$sides, [ $this->getSide(), self::ALL ] );
        foreach ( $sides as $side ) {
            $this->styles[ $side ] = NULL;
            $this->scripts[ $side ] = NULL;
            unset( $this->styles[ $side ] );
            unset( $this->scripts[ $side ] );
        }
    }

    private function checkSide( $side = NULL ) {
        if ( did_action( 'brain_assets_done' ) ) {
            return FALSE;
        }
        if ( ! did_action( 'brain_assets_ready' ) ) {
            return in_array( $side, self::$sides, TRUE ) ? $side : FALSE;
        }
        $valid = [ self::ALL, $this->getSide() ];
        return in_array( $side, $valid, TRUE ) ? $side : FALSE;
    }

}