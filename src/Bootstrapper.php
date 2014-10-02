<?php namespace Brain\Occipital;

class Bootstrapper implements BootstrapperInterface {

    private $container;
    private $side;

    public function __construct( ContainerInterface $container ) {
        $this->container = $container;
    }

    public function boot() {
        $this->bootLogin();
        is_admin() ? $this->bootAdmin() : $this->bootFront();
    }

    public function getContainer() {
        return $this->container;
    }

    public function getSide() {
        return $this->side;
    }

    private function bootLogin() {
        add_action( 'login_enqueue_scripts', function() {
            $this->side = ContainerInterface::LOGIN;
            $this->firesActions( 'login' );
        }, '-' . PHP_INT_MAX );
    }

    private function bootAdmin() {
        add_action( 'admin_enqueue_scripts', function() {
            $this->side = ContainerInterface::ADMIN;
            $this->firesActions( 'admin' );
        }, '-' . PHP_INT_MAX );
    }

    private function bootFront() {
        add_action( 'wp_enqueue_scripts', function() {
            $this->side = ContainerInterface::FRONT;
            $this->firesActions( 'front' );
        }, '-' . PHP_INT_MAX );
    }

    private function firesActions( $side ) {
        $hook = is_admin() ? 'admin_print_styles' : 'wp_print_styles';
        add_action( $hook, function() use($side) {
            $this->getContainer()->setSide( $this->getSide() );
            do_action( 'brain_assets_ready', $side, $this->getContainer() );
            do_action( "brain_assets_ready_{$side}", $this->getContainer() );
            $sides = [ ContainerInterface::LOGIN, ContainerInterface::ADMIN, ContainerInterface::FRONT ];
            do_action( 'brain_assets_remove', $this->getContainer() );
            do_action( "brain_assets_remove_{$side}", $this->getContainer() );
            $this->buildAssetsIterators();
            do_action( 'brain_assets_done' );
        }, '-' . PHP_INT_MAX );
    }

    private function buildAssetsIterators() {
        $side = $this->getSide();
        $side_styles = $this->getContainer()->getStyles( $side );
        $all_styles = $this->getContainer()->getStyles( Container::ALL );
        $side_scripts = $this->getContainer()->getScripts( $side );
        $all_scripts = $this->getContainer()->getScripts( Container::ALL );
        $this->getContainer()->setSideStyles( $this->mergeAssets( $side_styles, $all_styles ) );
        $this->getContainer()->setSideScripts( $this->mergeAssets( $side_scripts, $all_scripts ) );
    }

    private function mergeAssets( \Iterator $side, \Iterator $all ) {
        $iterator = new \AppendIterator();
        $iterator->append( $side );
        $iterator->append( $all );
        return $iterator;
    }

}