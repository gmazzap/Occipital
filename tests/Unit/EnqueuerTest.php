<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Enqueuer;

/**
 * Mocked wp_enqueue_*() and wp_*_is() functions are in tests/helpers/wp-functions.php
 * Mocked WP_Style/WP_Scripts classes are in tests/helpers/wp-style.php and tests/helpers/wp-script.php
 * Globals wp_styles/wp_scripts vars are build on setUp() and reset on tearDown() in TestCase class
 */
class EnqueuerTest extends TestCase {

    private function getAsset( $type = 'style', $id = '', $provided = [ ] ) {
        if ( ! $id ) {
            $id = uniqid( $type );
        }
        if ( $type === 'script' ) {
            $asset = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
            $file = "/js/{$id}.js";
            $asset->shouldReceive( 'getFooter' )->andReturn( TRUE );
            $data = (object) [ 'name' => "data_{$id}", 'data' => [ 'id' => $id ] ];
            $asset->shouldReceive( 'getLocalizeData' )->andReturn( [ $data ] );
        } else {
            $asset = \Mockery::mock( 'Brain\Occipital\StyleInterface' );
            $file = "/css/{$id}.css";
            $asset->shouldReceive( 'getMedia' )->andReturn( 'all' );
            $after = [ '.foo { display: none; }', '.bar { display: block; }' ];
            $asset->shouldReceive( 'getAfter' )->andReturn( $after );
        }
        $asset->shouldReceive( 'getHandle' )->andReturn( $id );
        $asset->shouldReceive( 'getDeps' )->andReturn( [ 'foo', 'bar' ] );
        $asset->shouldReceive( 'getVer' )->andReturn( 1 );
        $asset->shouldReceive( 'getSrc' )->andReturn( "http://www.example.com/{$file}" );
        $asset->shouldReceive( 'getProvided' )->andReturn( $provided );
        return $asset;
    }

    function testEnqueueDoNothingIfNoAssets() {
        $factory = function() {
            return [ ];
        };
        $e = new Enqueuer;
        assertFalse( $e->enqueue( $factory ) );
    }

    function testEnqueueNoAssets() {
        $factory = function() {
            return new \ArrayIterator;
        };
        $e = new Enqueuer;
        assertSame( [ 'styles' => FALSE, 'scripts' => FALSE ], $e->enqueue( $factory ) );
    }

    function testEnqueue() {
        $factory = function() {
            return new \ArrayIterator( [
                $this->getAsset( 'style', 'foo' ),
                $this->getAsset( 'style', 'bar' ),
                $this->getAsset( 'style', 'baz' ),
                $this->getAsset( 'script', 'foo' ),
                $this->getAsset( 'script', 'bar' ),
                $this->getAsset( 'script', 'baz' )
                ] );
        };
        \WP_Mock::wpFunction( 'wp_add_inline_style', [
            'times' => 6,
            'args'  => [
                \WP_Mock\Functions::anyOf( 'foo', 'bar', 'baz' ),
                \WP_Mock\Functions::type( 'string' )
            ]
        ] );
        \WP_Mock::wpFunction( 'wp_localize_script', [
            'times' => 3,
            'args'  => [
                \WP_Mock\Functions::anyOf( 'foo', 'bar', 'baz' ),
                \WP_Mock\Functions::anyOf( 'data_foo', 'data_bar', 'data_baz' ),
                \WP_Mock\Functions::type( 'array' )
            ]
        ] );
        global $wp_styles, $wp_scripts;
        $e = new Enqueuer;
        assertSame( [ 'styles' => TRUE, 'scripts' => TRUE ], $e->enqueue( $factory ) );
        assertSame( [ 'foo-css', 'bar-css', 'baz-css' ], array_keys( $wp_styles->registered ) );
        assertSame( [ 'foo', 'bar', 'baz' ], array_keys( $wp_scripts->registered ) );
        assertSame( [ 'foo-css', 'bar-css', 'baz-css' ], array_keys( $wp_styles->queue ) );
        assertSame( [ 'foo', 'bar', 'baz' ], array_keys( $wp_scripts->queue ) );
    }

    function testEnqueueStylesAndProvided() {
        $factory = function() {
            return new \ArrayIterator( [
                $this->getAsset( 'style', 'foo', [ 'prov1', 'prov2' ] ),
                $this->getAsset( 'style', 'bar', [ 'prov3' ] ),
                ] );
        };
        $dep = \Mockery::mock( '_WP_Dependency' );
        global $wp_styles;
        $wp_styles->registered = [
            'prov1'   => $dep,
            'prov2'   => clone $dep,
            'prov3'   => clone $dep,
            'parent1' => clone $dep,
            'parent2' => clone $dep,
            'parent3' => clone $dep
        ];
        $wp_styles->registered[ 'prov1' ]->deps = [ 'parent1' ];
        $wp_styles->registered[ 'prov2' ]->deps = [ 'parent2' ];
        $wp_styles->registered[ 'prov3' ]->deps = [ 'parent1', 'parent2', 'parent3' ];
        $wp_styles->registered[ 'prov1' ]->extra[ 'after' ] = [ '.prov1:{ color:#fff};' ];
        $wp_styles->registered[ 'prov2' ]->extra[ 'after' ] = [ '.prov2:{ color:#f0f};' ];
        $wp_styles->registered[ 'prov3' ]->extra[ 'after' ] = [ '.prov3:{ color:#ff0};' ];
        $wp_styles->to_do = [ 'prov2' ];
        $e = new Enqueuer;
        $expected_queue = [ 'parent1', 'parent2', 'parent3', 'foo-css', 'bar-css' ];
        $expected_data = [
            'foo' => [ '.prov1:{ color:#fff};', '.prov2:{ color:#f0f};' ],
            'bar' => [ '.prov3:{ color:#ff0};' ]
        ];
        assertSame( [ 'styles' => TRUE, 'scripts' => FALSE ], $e->enqueue( $factory ) );
        assertSame( [ 'prov1', 'prov2', 'prov3' ], $wp_styles->done );
        assertSame( [ ], $wp_styles->to_do );
        assertSame( $expected_queue, array_keys( $wp_styles->queue ) );
        assertSame( $expected_data, $e->getProvidedStylesData() );
        assertArrayHasKey( 'foo-css', $wp_styles->registered );
        assertArrayHasKey( 'bar-css', $wp_styles->registered );
    }

    function testEnqueueScriptsAndProvided() {
        $factory = function() {
            return new \ArrayIterator( [
                $this->getAsset( 'script', 'foo', [ 'prov1', 'prov2' ] ),
                $this->getAsset( 'script', 'bar', [ 'prov3' ] ),
                ] );
        };
        $dep = \Mockery::mock( '_WP_Dependency' );
        global $wp_scripts;
        $wp_scripts->registered = [
            'prov1'   => $dep,
            'prov2'   => clone $dep,
            'prov3'   => clone $dep,
            'parent1' => clone $dep,
            'parent2' => clone $dep,
            'parent3' => clone $dep
        ];
        $wp_scripts->registered[ 'prov1' ]->deps = [ 'parent1' ];
        $wp_scripts->registered[ 'prov2' ]->deps = [ 'parent2' ];
        $wp_scripts->registered[ 'prov3' ]->deps = [ 'parent1', 'parent2', 'parent3' ];
        $wp_scripts->registered[ 'prov1' ]->extra[ 'data' ] = 'var X = { "id": "prov1" };';
        $wp_scripts->registered[ 'prov2' ]->extra[ 'data' ] = 'var Y = { "id": "prov2" };';
        $wp_scripts->registered[ 'prov3' ]->extra[ 'data' ] = 'var Z = { "id": "prov3" };';
        $wp_scripts->to_do = [ 'prov2' ];
        $e = new Enqueuer;
        $expected_queue = [ 'parent1', 'parent2', 'parent3', 'foo', 'bar' ];
        $expected_data = [
            'foo' => [ 'var X = { "id": "prov1" };', 'var Y = { "id": "prov2" };' ],
            'bar' => [ 'var Z = { "id": "prov3" };' ]
        ];
        \WP_Mock::wpFunction( 'wp_localize_script', [
            'times' => 2,
            'args'  => [
                \WP_Mock\Functions::anyOf( 'foo', 'bar' ),
                \WP_Mock\Functions::anyOf( 'data_foo', 'data_bar' ),
                \WP_Mock\Functions::anyOf( [ 'id' => 'foo' ], [ 'id' => 'bar' ] )
            ]
        ] );
        assertSame( [ 'styles' => FALSE, 'scripts' => TRUE ], $e->enqueue( $factory ) );
        assertSame( [ 'prov1', 'prov2', 'prov3' ], $wp_scripts->done );
        assertSame( [ ], $wp_scripts->to_do );
        assertSame( $expected_queue, array_keys( $wp_scripts->queue ) );
        assertSame( $expected_data, $e->getProvidedScriptsData() );
        assertArrayHasKey( 'foo', $wp_scripts->registered );
        assertArrayHasKey( 'bar', $wp_scripts->registered );
    }

}