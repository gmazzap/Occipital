<?php namespace Brain\Occipital;

class BrainModule implements \Brain\Module {

    static $booted;

    public function boot( \Brain\Container $brain ) {
        if ( self::$booted ) {
            return;
        }
        self::$booted = TRUE;
        /**
         * Thanks to Thomas Scholz (toscho)
         * @see http://wordpress.stackexchange.com/a/127836/
         */
        if ( ! has_action( 'login_enqueue_scripts', 'wp_print_styles' ) ) {
            add_action( 'login_enqueue_scripts', 'wp_print_styles', 11 );
        }
        $brain[ 'occipital.bootstrapper' ]->boot();
        add_action( 'brain_assets_done', function() use($brain) {
            $enqueuer = $brain[ 'occipital.enqueuer' ];
            $enqueuer->setup( $brain[ 'occipital.styles' ], $brain[ 'occipital.scripts' ] );
            $enqueuer->enqueue();
        }, PHP_INT_MAX );
    }

    public function getBindings( \Brain\Container $brain ) {
        $brain[ 'occipital.container' ] = function() {
            return new Container;
        };
        $brain[ 'occipital.bootstrapper' ] = function($c) {
            return new Bootstrapper( $c[ 'occipital.container' ] );
        };
        $brain[ 'occipital.enqueuer' ] = function() {
            return new Enqueuer;
        };
        $brain[ 'occipital.scripts' ] = $brain->protect( function() use($brain) {
            /** @var \Brain\Occipital\Container $container */
            $container = $brain[ 'occipital.container' ];
            $scripts = $container->getSideScripts();
            return $scripts instanceof \Iterator && $scripts->valid() ?
                new Filter( $scripts, $container->getSide() ) :
                FALSE;
        } );
        $brain[ 'occipital.styles' ] = $brain->protect( function() use($brain) {
            /** @var \Brain\Occipital\Container $container */
            $container = $brain[ 'occipital.container' ];
            $styles = $container->getSideStyles();
            return $styles instanceof \Iterator && $styles->valid() ?
                new Filter( $styles, $container->getSide() ) :
                FALSE;
        } );
        $brain[ 'occipital.api' ] = function($c) {
            return new API( $c[ 'occipital.container' ] );
        };
    }

    public function getPath() {
        return dirname( dirname( __FILE__ ) );
    }

}