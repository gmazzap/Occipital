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
        $brain[ 'lobe.container' ]->init();
        add_action( 'admin_enqueue_script', function( $page ) use($brain) {
            $brain[ 'lobe.admin_page' ] = $page;
        }, -1 );
        /** @var \Brain\Occipital\Enqueuer $enqueuer */
        $enqueuer = $brain[ 'lobe.enqueuer' ];
        add_action( 'lobe_ready', function( $side ) use($enqueuer) {
            $enqueuer->setSide( $side );
        }, -1 );
        add_action( 'lobe_done', function() use($enqueuer, $brain) {
            /** @var \Brain\Occipital\Filter $scripts */
            $scripts = $brain[ 'lobe.scripts_filter' ]->__invoke();
            /** @var $scripts \Brain\Occipital\Filter */
            $styles = $brain[ 'lobe.styles_filter' ]->__invoke();
            $enqueuer->enqueueStyles( $styles );
            $enqueuer->enqueueScripts( $scripts );
            $enqueuer->registerProvided();
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
        $brain[ 'lobe.scripts_filter' ] = $brain->protect( function( $c ) {
            /** @var \Brain\Occipital\Container $container */
            $container = $c[ 'lobe.container' ];
            if ( $container->checkSide() ) {
                /** @var \SplObjectStorage $side_scripts */
                $side_scripts = $c[ 'lobe.container' ]->getScripts( $container->getSide() );
                /** @var \SplObjectStorage $all_scripts */
                $all_scripts = $c[ 'lobe.container' ]->getScripts( Container::ALL );
                $all_scripts->addAll( $side_scripts );
                return new Filter( $all_scripts );
            }
            throw new \RuntimeException( '', 'lobe-too-early-for-filter' );
        } );
        $brain[ 'lobe.styles_filter' ] = $brain->protect( function( $c ) {
            /** @var \Brain\Occipital\Container $container */
            $container = $c[ 'lobe.container' ];
            if ( $container->checkSide() ) {
                /** @var \SplObjectStorage $side_styles */
                $side_styles = $c[ 'lobe.container' ]->getStyles( $container->getSide() );
                /** @var \SplObjectStorage $all_styles */
                $all_styles = $c[ 'lobe.container' ]->getStyles( Container::ALL );
                $all_styles->addAll( $side_styles );
                return new Filter( $all_styles, $container->getSide(), $c[ 'lobe.admin_page' ] );
            }
            throw new \RuntimeException( '', 'lobe-too-early-for-filter' );
        } );
        $brain[ 'lobe.api' ] = function($c) {
            return new API( $c[ 'lobe.container' ] );
        };
    }

    public function getPath() {
        return dirname( dirname( __FILE__ ) );
    }

}