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
        add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
            $this->side = ContainerInterface::ADMIN;
            $this->firesActions( 'admin', $hook_suffix );
        }, '-' . PHP_INT_MAX );
    }

    private function bootFront() {
        add_action( 'wp_enqueue_scripts', function() {
            $this->side = ContainerInterface::FRONT;
            $this->firesActions( 'front' );
        }, '-' . PHP_INT_MAX );
    }

    private function firesActions( $side, $hook_suffix = '' ) {
        $hook = is_admin() ? "admin_print_styles-{$hook_suffix}" : 'wp_print_styles';
        add_action( $hook, function() use($side) {
            $this->getContainer()->setSide( $this->getSide() );
            do_action( 'brain_assets_ready', $side, $this->getContainer() );
            do_action( "brain_assets_ready_{$side}", $this->getContainer() );
            do_action( 'brain_assets_remove', $this->getContainer() );
            do_action( "brain_assets_remove_{$side}", $this->getContainer() );
            $this->buildAssetsIterator();
            do_action( 'brain_assets_done' );
        }, '-' . PHP_INT_MAX );
    }

    private function buildAssetsIterator() {
        $side = $this->getSide();
        $side_styles = $this->getContainer()->getStyles( $side );
        $all_styles = $this->getContainer()->getStyles( Container::ALL );
        $side_scripts = $this->getContainer()->getScripts( $side );
        $all_scripts = $this->getContainer()->getScripts( Container::ALL );
        $iterator = $this->mergeAssets( $side_styles, $all_styles, $side_scripts, $all_scripts );
        $this->getContainer()->setAssetsIterator( $iterator );
    }

    private function mergeAssets( $side_styles, $all_styles, $side_scripts, $all_scripts ) {
        $iterator = new \AppendIterator();
        $iterator->append( $side_styles );
        $iterator->append( $all_styles );
        $iterator->append( $side_scripts );
        $iterator->append( $all_scripts );
        return $iterator;
    }

}