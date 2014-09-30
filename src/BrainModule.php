<?php namespace Brain\Occipital;

class BrainModule implements \Brain\Module {

    static $booted;

    public function boot( \Brain\Container $brain ) {
        if ( self::$booted ) {
            return;
        }
        self::$booted = TRUE;
        if ( ! has_action( 'login_enqueue_scripts', 'wp_print_styles' ) ) {
            add_action( 'login_enqueue_scripts', 'wp_print_styles', 99999 );
        }
        $brain[ 'occipital.container' ]->init();
        add_action( 'brain_assets_done', function() use($brain) {
            $enqueuer = $brain[ 'occipital.enqueuer' ];
            $enqueuer->enqueueStyles( $brain[ 'occipital.styles' ] );
            $enqueuer->enqueueScripts( $brain[ 'occipital.scripts' ] );
            $enqueuer->registerProvided();
        }, PHP_INT_MAX );
    }

    public function getBindings( \Brain\Container $brain ) {
        $brain[ 'occipital.container' ] = function() {
            return new Container;
        };
        $brain[ 'occipital.enqueuer' ] = function() {
            return new Enqueuer;
        };
        $brain[ 'occipital.scripts' ] = $brain->protect( function() use($brain) {
            /** @var \Brain\Occipital\Container $container */
            $container = $brain[ 'occipital.container' ];
            $side = $container->getSide();
            return new Filter( $container->getSideScripts(), $side );
        } );
        $brain[ 'occipital.styles' ] = $brain->protect( function() use($brain) {
            /** @var \Brain\Occipital\Container $container */
            $container = $brain[ 'occipital.container' ];
            $side = $container->getSide();
            return new Filter( $container->getSideStyles(), $side );
        } );
        $brain[ 'occipital.api' ] = function($c) {
            return new API( $c[ 'occipital.container' ] );
        };
    }

    public function getPath() {
        return dirname( dirname( __FILE__ ) );
    }

}