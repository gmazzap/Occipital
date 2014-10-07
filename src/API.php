<?php namespace Brain\Occipital;

class API {

    private $container;
    private static $types = [
        'style'  => EnqueuableInterface::STYLE,
        'script' => EnqueuableInterface::SCRIPT
    ];

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
        if ( did_action( 'brain_assets_done' ) ) {
            return new \WP_Error( 'occipital-too-late-for-assets' );
        }
        try {
            $args = $this->checkArgs( $what, $handle, $where, $class );
            if ( ! $args ) {
                throw new \InvalidArgumentException;
            }
            $asset = $this->getAssetToAdd( $args, $data );
            if ( ! $asset instanceof EnqueuableInterface ) {
                throw new \UnexpectedValueException;
            }
            $cb = $asset instanceof ScriptInterface ? 'addScript' : 'addStyle';
            return $this->getContainer()->$cb( $asset, $args[ 'where' ] );
        } catch ( \Exception $e ) {
            return \Brain\exception2WPError( $e, 'occipital' );
        }
    }

    /**
     * Remove a script from queue.
     *
     * @param string $handle Script handle
     * @return \Brain\Occipital\Container
     */
    public function removeScript( $handle ) {
        try {
            $container = $this->getContainer();
            $container->removeScript( $handle );
            return $container;
        } catch ( Exception $e ) {
            return \Brain\exception2WPError( $e, 'occipital' );
        }
    }

    /**
     * Remove a style from queue.
     *
     * @param string $handle Style handle
     * @return \Brain\Occipital\Container
     */
    public function removeStyle( $handle ) {
        try {
            $container = $this->getContainer();
            $container->removeStyle( $handle );
            return $container;
        } catch ( Exception $e ) {
            return \Brain\exception2WPError( $e, 'occipital' );
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
    public function addScript( $handle, Array $data = [ ], $where = NULL ) {
        return $this->add( self::$types[ 'script' ], $handle, $data, $where );
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
        return $this->add( self::$types[ 'style' ], $handle, $data, $where );
    }

    /**
     * Enqueue a script object into WordPress queue on frontend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addFrontScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, ContainerInterface::FRONT );
    }

    /**
     * Enqueue a script object into WordPress queue on backend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addAdminScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, ContainerInterface::ADMIN );
    }

    /**
     * Enqueue a script object into WordPress queue on login page.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addLoginScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, ContainerInterface::LOGIN );
    }

    /**
     * Enqueue a script object into WordPress queue everywhere: frontend, backend and login page.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\ScriptInterface|\WP_Error
     */
    public function addSiteScript( $handle, Array $data = [ ] ) {
        return $this->addScript( $handle, $data, ContainerInterface::ALL );
    }

    /**
     * Enqueue a style object into WordPress queue on frontend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addFrontStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, ContainerInterface::FRONT );
    }

    /**
     * Enqueue a style object into WordPress queue on backend.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addAdminStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, ContainerInterface::ADMIN );
    }

    /**
     * Enqueue a style object into WordPress queue on login pages.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addLoginStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, ContainerInterface::LOGIN );
    }

    /**
     * Enqueue a style object into WordPress queue everywhere: frontend, backend and login page.
     *
     * @param string $handle    The asset handle
     * @param array $data       An array of properties to set in asset object
     * @return \Brain\Occipital\StyleInterface|\WP_Error
     */
    public function addSiteStyle( $handle, Array $data = [ ] ) {
        return $this->addStyle( $handle, $data, ContainerInterface::ALL );
    }

    /**
     * Get an asset object from ontainer.
     *
     * @param int $what
     * @param string $handle
     * @return Brain\Occipital\EnqueuableInterface
     */
    public function getAsset( $what, $handle ) {
        if ( ! in_array( $what, self::$types, TRUE ) || ! is_string( $handle ) || empty( $handle ) ) {
            return;
        }
        if ( $what === EnqueuableInterface::STYLE ) {
            return $this->getContainer()->getStyle( $handle );
        }
        return $this->getContainer()->getScript( $handle );
    }

    /**
     * Get a style object from ontainer.
     *
     * @param string $handle
     * @return Brain\Occipital\StyleInterface
     */
    public function getStyle( $handle ) {
        return $this->getAsset( EnqueuableInterface::STYLE, $handle );
    }

    /**
     * Get a script object from ontainer.
     *
     * @param string $handle
     * @return Brain\Occipital\ScriptInterface
     */
    public function getScript( $handle ) {
        return $this->getAsset( EnqueuableInterface::SCRIPT, $handle );
    }

    /**
     * Return Container instance
     *
     * @return Brain\Occipital\ContainerInterface
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * @internal
     */
    private function getAssetToAdd( $args, $data ) {
        $asset_class = $this->getAssetClass( $args[ 'what' ], $args[ 'class' ] );
        $asset = new $asset_class( $args[ 'handle' ] );
        if ( ! empty( $data ) ) {
            $asset = $this->setupAssetData( $asset, $data );
        }
        return $asset;
    }

    /**
     * @internal
     */
    private function setupAssetData( $asset, $data ) {
        foreach ( $data as $key => $value ) {
            $method = strpos( $key, 'set' ) === 0 ? $key : 'set' . ucfirst( $key );
            if ( $method === 'setProvide' ) {
                $method = 'setProvided';
            }
            if ( method_exists( $asset, $method ) ) {
                $asset->$method( $value );
            }
        }
        return $asset;
    }

    /**
     * @internal
     */
    private function checkArgs( $what, $_handle, $_where, $r_class ) {
        $handle = $this->checkHandle( $_handle );
        if ( ! in_array( $what, self::$types, TRUE ) || empty( $handle ) ) {
            return FALSE;
        }
        $where = $this->mapWhere( $_where ) ? : NULL;
        $class = class_exists( $r_class ) ? $r_class : NULL;
        return compact( 'what', 'handle', 'where', 'class' );
    }

    /**
     * @internal
     */
    private function checkHandle( $handle ) {
        return is_string( $handle ) ? preg_replace( '/[^-\w\.]/', '', $handle ) : FALSE;
    }

    /**
     * @internal
     */
    private function getAssetClass( $what, $class ) {
        $default = $what === self::$types[ 'script' ] ?
            'Brain\Occipital\Script' :
            'Brain\Occipital\Style';
        if ( is_null( $class ) ) {
            $class = $default;
        }
        if ( $class !== $default ) {
            $ref = new \ReflectionClass( $class );
            $interface = $what === self::$types[ 'script' ] ?
                'ScriptInterface' :
                'StyleInterface';
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
        $valid = [
            ContainerInterface::ADMIN,
            ContainerInterface::FRONT,
            ContainerInterface::LOGIN,
            ContainerInterface::ALL,
            NULL
        ];
        if ( in_array( $where, $valid, TRUE ) ) {
            return $where;
        }
        $map = [
            'admin'    => ContainerInterface::ADMIN,
            'back'     => ContainerInterface::ADMIN,
            'backend'  => ContainerInterface::ADMIN,
            'front'    => ContainerInterface::FRONT,
            'frontend' => ContainerInterface::FRONT,
            'public'   => ContainerInterface::FRONT,
            'login'    => ContainerInterface::LOGIN,
            'register' => ContainerInterface::LOGIN,
            '*'        => ContainerInterface::ALL,
            'all'      => ContainerInterface::ALL
        ];
        $key = is_string( $where ) ? strtolower( $where ) : '';
        return array_key_exists( $key, $map ) ? $map[ $key ] : FALSE;
    }

}