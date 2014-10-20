<?php namespace Brain\Occipital\Tests;

use Brain\Container as Brain;

class TestCase extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        global $wp_scripts, $wp_styles;
        $wp_scripts = new \WP_Scripts;
        $wp_styles = new \WP_Styles;
        \WP_Mock::setUp();
        Brain::boot( new \Pimple\Container, FALSE );
    }

    public function tearDown() {
        unset( $GLOBALS[ 'wp_scripts' ], $GLOBALS[ 'wp_styles' ] );
        Brain::flush();
        \WP_Mock::tearDown();
        \Mockery::close();
    }

}