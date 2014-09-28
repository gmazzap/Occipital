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
        $brain[ 'lobe.container' ]->init();
        add_action( 'admin_enqueue_script', function( $page ) use($brain) {
            $brain[ 'lobe.admin_page' ] = $page;
        }, -1 );
        add_action( 'lobe_done', function() use($brain) {
            $brain[ 'lobe.worker' ]->work();
        }, PHP_INT_MAX );
    }

    public function getBindings( \Brain\Container $brain ) {
        $brain[ 'lobe.admin_page' ] = FALSE;
        $brain[ 'lobe.container' ] = function() {
            return new Container;
        };
        $brain[ 'lobe.enqueuer' ] = function() {
            return new Enqueuer;
        };
        $brain[ 'lobe.scripts' ] = $brain->protect( function( $c ) {
            /** @var \Brain\Occipital\Container $container */
            $container = $c[ 'lobe.container' ];
            if ( $container->checkSide() ) {
                $side = $container->getSide();
                return new Filter( $container->getSideScripts(), $side, $c[ 'lobe.admin_page' ] );
            }
        } );
        $brain[ 'lobe.styles' ] = $brain->protect( function( $c ) {
            /** @var \Brain\Occipital\Container $container */
            $container = $c[ 'lobe.container' ];
            $side = $container->getSide();
            if ( $container->checkSide() ) {
                return new Filter( $container->getSideStyles(), $side, $c[ 'lobe.admin_page' ] );
            }
        } );
        $brain[ 'lobe.worker' ] = function($c) {
            return new Worker( $c[ 'lobe.enqueuer' ], $c[ 'lobe.scripts' ], $c[ 'lobe.styles' ] );
        };
        $brain[ 'lobe.api' ] = function($c) {
            return new API( $c[ 'lobe.container' ] );
        };
    }

    public function getPath() {
        return dirname( dirname( __FILE__ ) );
    }

}