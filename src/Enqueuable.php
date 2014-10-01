<?php namespace Brain\Occipital;

abstract class Enqueuable implements EnqueuableInterface {

    use \Brain\Contextable;

    protected $context;

    public function __construct( $handle = NULL ) {
        $this->context = new \ArrayObject;
        if ( ! is_null( $handle ) ) {
            $this->setHandle( $handle );
        }
    }

    public function __call( $name, $arguments ) {
        if ( $name === 'provide' ) {
            return call_user_func_array( [ $this, 'setProvided' ], $arguments );
        }
        $method = 'set' . ucfirst( $name );
        if ( method_exists( $this, $method ) ) {
            return call_user_func_array( [ $this, $method ], $arguments );
        }
    }

    public function getCondition() {
        return $this->getContext( 'context', 'condition' );
    }

    public function getDeps() {
        return $this->getContext( 'context', 'deps' ) ? : [ ];
    }

    public function getHandle() {
        return $this->getContext( 'context', 'handle' );
    }

    public function getSrc() {
        return $this->getContext( 'context', 'src' );
    }

    public function getVer() {
        return $this->getContext( 'context', 'ver' );
    }

    public function getProvided() {
        return $this->getContext( 'context', 'provide' ) ? : [ ];
    }

    public function setCondition( $condition ) {
        if ( is_callable( $condition ) ) {
            $this->setContext( 'context', 'condition', $condition );
        }
        return $this;
    }

    public function setDeps( Array $dependencies = [ ] ) {
        $sane = $this->sanitizeArray( $dependencies );
        if ( is_array( $sane ) ) {
            $this->setContext( 'context', 'deps', $sane );
        }
        return $this;
    }

    public function setHandle( $handle ) {
        if ( is_string( $handle ) ) {
            $this->setContext( 'context', 'handle', filter_var( $handle, FILTER_SANITIZE_STRING ) );
        }
        return $this;
    }

    public function setSrc( $src ) {
        if ( is_string( $src ) ) {
            $this->setContext( 'context', 'src', filter_var( $src, FILTER_SANITIZE_URL ) );
        }
        return $this;
    }

    public function setVer( $ver = NULL ) {
        if ( is_string( $ver ) || is_numeric( $ver ) || is_null( $ver ) ) {
            $this->setContext( 'context', 'ver', $ver );
        }
        return $this;
    }

    public function setProvided( $provided ) {
        $sane = $this->sanitizeArray( $provided );
        if ( is_array( $sane ) ) {
            $this->setContext( 'context', 'provide', $sane );
        }
        return $this;
    }

    private function sanitizeArray( $var ) {
        if ( ! is_string( $var ) && ! is_array( $var ) ) {
            return FALSE;
        }
        $filtered = array_filter( (array) $var, function( $el ) {
            return is_string( $el ) && ! empty( $el );
        } );
        return ! empty( $filtered ) ? array_map( function( $el ) {
                return filter_var( $el, FILTER_SANITIZE_STRING );
            }, $filtered ) : FALSE;
    }

}