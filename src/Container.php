<?php namespace Brain\Occipital;

class Container implements ContainerInterface {

    private static $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
    private $scripts = [ ];
    private $styles = [ ];
    private $side;

    public function __construct() {
        foreach ( self::$sides as $side ) {
            $this->setStorage( $side );
        }
    }

    public function init() {
        $this->initLogin();
        is_admin() ? $this->initAdmin() : $this->initFront();
    }

    public function addScript( ScriptInterface $script, $side = NULL ) {
        $where = $this->checkSide( $side );
        if ( ! $where ) {
            throw new \UnexpectedValueException;
        }
        $this->getScripts( $where )->attach( $script );
        return $script;
    }

    public function addStyle( StyleInterface $style, $side = NULL ) {
        $where = $this->checkSide( $side );
        if ( ! $where ) {
            throw new \UnexpectedValueException;
        }
        $this->getStyles( $where )->attach( $style );
        return $style;
    }

    /**
     * {@inheritdoc}
     *
     * @return \SplObjectStorage
     * @throws \InvalidArgumentException
     */
    public function getScripts( $side = NULL ) {
        $scripts = $this->scripts;
        if ( is_null( $side ) ) {
            return $scripts;
        }
        if ( in_array( $side, self::$sides, TRUE ) && isset( $scripts[ $side ] ) ) {
            return $scripts[ $side ];
        }
        throw new \InvalidArgumentException;
    }

    /**
     * {@inheritdoc}
     *
     * @return \SplObjectStorage
     * @throws \InvalidArgumentException
     */
    public function getStyles( $side = NULL ) {
        $styles = $this->styles;
        if ( is_null( $side ) ) {
            return $styles;
        }
        if ( in_array( $side, self::$sides, TRUE ) && isset( $styles[ $side ] ) ) {
            return $styles[ $side ];
        }
        throw new \InvalidArgumentException;
    }

    public function getSide() {
        return $this->side;
    }

    /**
     * {@inheritdoc}
     *
     * @return \SplObjectStorage
     * @throws \RuntimeException
     */
    public function getSideStyles() {
        $side = $this->getSide();
        if ( empty( $side ) ) {
            throw new \RuntimeException;
        }
        /** @var \SplObjectStorage $side_styles */
        $side_styles = $this->getStyles( $this->getSide() );
        /** @var \SplObjectStorage $all_styles */
        $all_styles = $this->getStyles( Container::ALL );
        $all_styles->addAll( $side_styles );
        return $all_styles;
    }

    /**
     * {@inheritdoc}
     *
     * @return \SplObjectStorage
     * @throws \RuntimeException
     */
    public function getSideScripts() {
        $side = $this->getSide();
        if ( empty( $side ) ) {
            throw new \RuntimeException;
        }
        /** @var \SplObjectStorage $side_scripts */
        $side_scripts = $this->getScripts( $this->getSide() );
        /** @var \SplObjectStorage $all_scripts */
        $all_scripts = $this->getScripts( Container::ALL );
        $all_scripts->addAll( $side_scripts );
        return $all_scripts;
    }

    private function initLogin() {
        add_action( 'login_enqueue_scripts', function() {
            $this->side = self::LOGIN;
            do_action( 'lobe_ready', $this->side, $this );
            do_action( "lobe_ready_{$this->side}", $this );
            $this->unsetStorage( [ self::ADMIN, self::FRONT ] );
            do_action( 'lobe_done' );
        }, '-' . PHP_INT_MAX );
    }

    private function initAdmin() {
        add_action( 'admin_enqueue_scripts', function($page) {
            $this->side = self::ADMIN;
            do_action( 'lobe_ready', $this->side, $this, $page );
            do_action( "lobe_ready_{$this->side}", $this, $page );
            $this->unsetStorage( [ self::LOGIN, self::FRONT ] );
            do_action( 'lobe_done' );
        }, '-' . PHP_INT_MAX );
    }

    private function initFront() {
        add_action( 'wp_enqueue_scripts', function() {
            $this->side = self::FRONT;
            do_action( 'lobe_ready', $this->side, $this );
            do_action( "lobe_ready_{$this->side}", $this );
            $this->unsetStorage( [ self::LOGIN, self::ADMIN ] );
            do_action( 'lobe_done' );
        }, '-' . PHP_INT_MAX );
    }

    private function setStorage( $side ) {
        $this->styles[ $side ] = new \SplObjectStorage;
        $this->scripts[ $side ] = new \SplObjectStorage;
    }

    private function unsetStorage( $sides ) {
        foreach ( $sides as $side ) {
            if ( isset( $this->scripts[ $side ] ) ) {
                $this->scripts[ $side ] = NULL;
                unset( $this->scripts[ $side ] );
            }
        }
    }

    private function checkSide( $side = NULL ) {
        if ( did_action( 'lobe_done' ) ) {
            return FALSE;
        }
        $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
        if ( is_null( $side ) ) {
            $side = $this->getSide();
        }
        if ( is_null( $side ) || ! in_array( $side, $sides, TRUE ) ) {
            return FALSE;
        }
        if ( $side === self::ALL || ( $side === $this->getSide() || ! did_action( 'lobe_ready' ) ) ) {
            return $side;
        }
        return FALSE;
    }

}