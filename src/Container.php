<?php namespace Brain\Lobe;

class Container implements ContainerInterface {

    private $scripts = [ ];
    private $styles = [ ];
    private $side;

    public function __construct() {
        $this->styles[ self::ALL ] = new \SplObjectStorage;
        $this->scripts[ self::ALL ] = new \SplObjectStorage;
    }

    public function init() {
        $this->initLogin();
        is_admin() ? $this->initAdmin() : $this->initFront();
    }

    public function addScript( ScriptInterface $s, $side = NULL ) {
        $where = $this->checkSide( $side );
        if ( ! $where ) {
            throw new \UnexpectedValueException;
        }
        $this->scripts[ $where ]->attach( $s );
        return $s;
    }

    public function addStyle( StyleInterface $s, $side = NULL ) {
        $where = $this->checkSide( $side );
        if ( ! $where ) {
            throw new \UnexpectedValueException;
        }
        $this->styles[ $where ]->attach( $s );
        return $s;
    }

    public function getScripts( $side = NULL ) {
        $scripts = $this->scripts;
        if ( ! is_null( $side ) && $this->checkSide() ) {
            $scripts = $scripts[ $side ];
        }
        return $scripts;
    }

    public function getStyles( $side = NULL ) {
        $styles = $this->scripts;
        if ( ! is_null( $side ) && $this->checkSide() ) {
            $styles = $styles[ $side ];
        }
        return $styles;
    }

    public function getSide() {
        return $this->side;
    }

    private function initLogin() {
        add_action( 'login_enqueue_scripts', function() {
            $this->side = self::LOGIN;
            $this->setStorage( $this->side );
            do_action( 'lobe_ready', 'login', $this );
            do_action( 'lobe_ready_login', $this );
            do_action( 'lobe_done' );
        } );
    }

    private function initAdmin() {
        add_action( 'admin_enqueue_styles', function($page) {
            $this->side = self::ADMIN;
            $this->setStorage( $this->side );
            do_action( 'lobe_ready', 'admin', $this, $page );
            do_action( 'lobe_ready_admin', $this, $page );
            do_action( 'lobe_done' );
        } );
    }

    private function initFront() {
        add_action( 'wp_enqueue_styles', function() {
            $this->side = self::FRONT;
            $this->setStorage( $this->side );
            do_action( 'lobe_ready', 'front', $this );
            do_action( 'lobe_ready_admin', $this );
            do_action( 'lobe_done' );
        } );
    }

    private function setStorage( $side ) {
        $this->scripts[ $side ] = new \SplObjectStorage;
        $this->styles[ $side ] = new \SplObjectStorage;
    }

    private function checkSide( $side = NULL ) {
        $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
        if ( ! is_null( $side ) ) {
            return in_array( $sides, TRUE ) ? (int) $side : FALSE;
        }
        if ( did_action( 'lobe_ready' )
            && in_array( $this->side, $sides, TRUE )
            && isset( $this->scripts[ $this->side ] )
            && isset( $this->styles[ $this->side ] )
            && $this->scripts[ $this->side ] instanceof \SplObjectStorage
            && $this->styles[ $this->side ] instanceof \SplObjectStorage ) {
            return $this->side;
        }
        return FALSE;
    }

}