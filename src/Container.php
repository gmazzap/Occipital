<?php namespace Brain\Occipital;

class Container implements ContainerInterface {

    private static $sides = [ self::LOGIN, self::ADMIN, self::FRONT, self::ALL ];
    private $scripts = [ ];
    private $styles = [ ];
    private $all_scripts;
    private $all_styles;
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
        $scripts = $this->getScripts( $where );
        $scripts[ $script->getHandle() ] = $script;
        return $script;
    }

    public function addStyle( StyleInterface $style, $side = NULL ) {
        $where = $this->checkSide( $side );
        if ( ! $where ) {
            throw new \UnexpectedValueException;
        }
        $styles = $this->getStyles( $where );
        $styles[ $style->getHandle() ] = $style;
        return $style;
    }

    public function removeScript( $script ) {
        /** @var \ArrayIterator $scripts */
        $scripts = $this->getSideScripts();
        $handle = FALSE;
        if ( $script instanceof EnqueuableInterface ) {
            $handle = $script->getHandle();
        } elseif ( is_string( $script ) ) {
            $handle = $script;
        }
        if ( $handle && $scripts->offsetExists( $handle ) ) {
            $scripts->offsetUnset( $script->getHandle() );
        }
        wp_dequeue_script( $handle );
    }

    public function removeStyle( $style ) {
        /** @var \ArrayIterator $styles */
        $styles = $this->getSideStyles();
        $handle = FALSE;
        if ( $style instanceof EnqueuableInterface ) {
            $handle = $style->getHandle();
        } elseif ( is_string( $style ) ) {
            $handle = $style;
        }
        if ( $handle && $style->offsetExists( $handle ) ) {
            $styles->offsetUnset( $style->getHandle() );
        }
        wp_dequeue_script( $handle );
    }

    /**
     * {@inheritdoc}
     *
     * @return array|\ArrayIterator
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
     * @return array|\ArrayIterator
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

    public function getSideStyles() {
        if ( is_null( $this->getSide() ) ) {
            throw new \RuntimeException;
        }
        return $this->all_styles;
    }

    public function getSideScripts() {
        if ( is_null( $this->getSide() ) ) {
            throw new \RuntimeException;
        }
        return $this->all_scripts;
    }

    private function initLogin() {
        add_action( 'login_enqueue_scripts', function() {
            $this->side = self::LOGIN;
            $this->firesActions( $this->side );
        }, '-' . PHP_INT_MAX );
    }

    private function initAdmin() {
        add_action( 'admin_enqueue_scripts', function() {
            $this->side = self::ADMIN;
            $this->firesActions( $this->side );
        }, '-' . PHP_INT_MAX );
    }

    private function initFront() {
        add_action( 'wp_enqueue_scripts', function() {
            $this->side = self::FRONT;
            $this->firesActions( $this->side );
        }, '-' . PHP_INT_MAX );
    }

    private function setStorage( $side ) {
        $this->styles[ $side ] = new \ArrayIterator;
        $this->scripts[ $side ] = new \ArrayIterator;
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

    private function firesActions( $side ) {
        do_action( 'brain_assets_ready', $side, $this );
        do_action( "brain_assets_ready_{$side}", $this );
        $this->unsetStorage( array_diff( [ self::LOGIN, self::ADMIN, self::FRONT ], [$side ] ) );
        do_action( 'brain_assets_remove' );
        do_action( "brain_assets_remove_{$side}", $this );
        $this->buildAssetsIterators();
        do_action( 'brain_assets_done' );
    }

    private function buildAssetsIterators() {
        $side = $this->getSide();
        if ( empty( $side ) ) {
            throw new \RuntimeException;
        }
        $side_styles = $this->getStyles( $side );
        $all_styles = $this->getStyles( Container::ALL );
        $side_scripts = $this->getScripts( $side );
        $all_scripts = $this->getScripts( Container::ALL );
        $this->all_styles = $this->mergeAssets( $side_styles, $all_styles );
        $this->all_scripts = $this->mergeAssets( $side_scripts, $all_scripts );
    }

    private function mergeAssets( \Iterator $side, \Iterator $all ) {
        $iterator = new \AppendIterator();
        $iterator->append( $side );
        $iterator->append( $all );
        return $iterator;
    }

}