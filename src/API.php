<?php namespace Brain\Occipital;

class API {

    private $container;

    const SCRIPT = 101;
    const STYLE = 202;

    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Enqueue EnqueuableInterface object (script or style) into WordPress queue.
     * Is used by all the other API functions for the scope
     *
     * @param int $what         Used internally to choose if enqueue a script or style
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @param int|string $where Where add the script: backend, frontend or login page.
     * @param string $class     Alternative class name for the asset, must instantiare proper interface
     * @return \Brain\Occipital\EnqueuableInterface|\WP_Error
     * @throws \InvalidArgumentException
     */
    public function add( $what, $handle, Array $data = [ ], $where = NULL, $class = NULL ) {
        if ( did_action( 'lobe_done' ) ) {
            return new \WP_Error( 'lobe-too-late-for-assets' );
        }
        try {
            $args = $this->checkArgs( $what, $handle, $where, $class );
            if ( ! $args ) {
                throw new \InvalidArgumentException;
            }
            $cb = $args[ 'what' ] === self::SCRIPT ? 'addScript' : 'addStyle';
            $asset_class = $this->getAssetClass( $args[ 'what' ], $args[ 'class' ] );
            $asset = new $asset_class( $args[ 'handle' ] );
            if ( ! empty( $data ) ) {
                $valid = array_filter( array_keys( $data ), function( $prop ) use($asset) {
                    return method_exists( $asset, 'set' . ucfirst( $prop ) );
                } );
                if ( empty( $valid ) ) {
                    return $asset;
                }
                foreach ( $valid as $key ) {
                    $method = 'set' . ucfirst( $key );
                    $asset->$method( $data[ $key ] );
                }
            }
            return $this->container->$cb( $asset, $args[ 'where' ] );
        } catch ( \Exception $e ) {
            return \Brain\exception2WPError( $e, 'lobe' );
        }
    }

    /**
     * Enqueue a script object into WordPress queue.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @param int|string $where Where add the script: backend, frontend or login page.
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addScript( $handle, Array $data = [ ], $where ) {
        return $this->add( self::SCRIPT, $handle, $data, $where );
    }

    /**
     * Enqueue a style object into WordPress queue.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @param int|string $where Where add the script: backend, frontend or login page.
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addStyle( $handle, Array $data = [ ], $where = NULL ) {
        return $this->add( self::STYLE, $handle, $data, $where = NULL );
    }

    /**
     * Enqueue a script object into WordPress queue on frontend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addFrontScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, Container::FRONT );
    }

    /**
     * Enqueue a script object into WordPress queue on backend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addAdminScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, Container::ADMIN );
    }

    /**
     * Enqueue a script object into WordPress queue on login page.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addLoginScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, Container::LOGIN );
    }

    /**
     * Enqueue a script object into WordPress queue everywhere: frontend, backend and login page.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addSiteScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, Container::ALL );
    }

    /**
     * Enqueue a style object into WordPress queue on frontend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addFrontStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, Container::ALL );
    }

    /**
     * Enqueue a style object into WordPress queue on backend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addAdminStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, Container::ADMIN );
    }

    /**
     * Enqueue a style object into WordPress queue on login pages.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addLoginStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, Container::ADMIN );
    }

    /**
     * Enqueue a style object into WordPress queue everywhere: frontend, backend and login page.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addSiteStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, Container::ALL );
    }

    /**
     * @internal
     */
    private function checkArgs( $what, $handle, $where, $class ) {
        if ( ! in_array( $what, [ self::SCRIPT, self::STYLE ], TRUE ) ) {
            return FALSE;
        }
        if ( ! is_string( $handle ) || empty( $handle ) ) {
            return FALSE;
        }
        $handle = preg_replace( '/[^-\w]/', '', $handle );
        $where = $this->mapWhere( $where );
        if ( $where === FALSE ) {
            return FALSE;
        }
        if ( ! is_null( $class ) && ! class_exists( $class ) ) {
            return FALSE;
        }
        return compact( 'what', 'handle', 'where', 'class' );
    }

    /**
     * @internal
     */
    private function getAssetClass( $what, $class ) {
        $default = $what === self::SCRIPT ? 'Brain\Occipital\Script' : 'Brain\Occipital\Style';
        if ( is_null( $class ) ) {
            $class = $default;
        }
        if ( $class !== $default ) {
            $ref = new \ReflectionClass( $class );
            $interface = $what === self::SCRIPT ? 'ScriptInterface' : 'StyleInterface';
            if ( ! $ref->implementsInterface( $interface ) ) {
                $class = $default;
            }
        }
        return $class;
    }

    /**
     * @internal
     */
    private function mapWhere( $where ) {
        $valid = [ Container::ADMIN, Container::FRONT, Container::LOGIN, Container::ALL ];
        if ( is_null( $where ) || in_array( $where, $valid, TRUE ) ) {
            return $where;
        }
        if ( ! is_string( $where ) ) {
            return FALSE;
        }
        $where = strtolower( $where );
        $maps = [
            'admin'    => Container::ADMIN,
            'back'     => Container::ADMIN,
            'backend'  => Container::ADMIN,
            'front'    => Container::FRONT,
            'frontend' => Container::FRONT,
            'public'   => Container::FRONT,
            'login'    => Container::LOGIN,
            '*'        => Container::ALL,
            'all'      => Container::ALL
        ];
        $search = array_search( $where, $maps );
        return $search !== FALSE ? $maps[ $search ] : FALSE;
    }

}