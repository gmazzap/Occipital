<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Enqueuer;

class EnqueuerTest extends TestCase {

    private function getStyle( $id = '' ) {
        if ( ! $id ) {
            $id = uniqid( 'style_' );
        }
        $style = \Mockery::mock( 'Brain\Occipital\StyleInterface' );
        $style->shouldReceive( 'getHandle' )->andReturn( $id );
        $style->shouldReceive( 'getSrc' )->andReturn( "http://www.example.com/js/{$id}.js" );
        $style->shouldReceive( 'getDeps' )->andReturn( [ 'foo', 'bar' ] );
        $style->shouldReceive( 'getVer' )->andReturn( 1 );
        $style->shouldReceive( 'getMedia' )->andReturn( 'all' );
        $style->shouldReceive( 'getProvided' )->andReturn( [ 'white', "prov_by_{$id}" ] );
        return $style;
    }

    private function getScript( $id = '' ) {
        if ( ! $id ) {
            $id = uniqid( 'script_' );
        }
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $script->shouldReceive( 'getHandle' )->andReturn( $id );
        $script->shouldReceive( 'getSrc' )->andReturn( "http://www.example.com/js/{$id}.js" );
        $script->shouldReceive( 'getDeps' )->andReturn( [ 'foo', 'bar' ] );
        $script->shouldReceive( 'getVer' )->andReturn( 1 );
        $script->shouldReceive( 'isFooter' )->andReturn( TRUE );
        $data = [ 'name' => "data_{$id}", 'data' => [ 'id' => $id ] ];
        $script->shouldReceive( 'getLocalizeData' )->andReturn( (object) $data );
        $script->shouldReceive( 'getProvided' )->andReturn( [ 'grey', "prov_by_{$id}" ] );
        return $script;
    }

    function testEnqueueScriptsNullIfNoScripts() {
        $cb = function() {
            return TRUE;
        };
        $e = new Enqueuer;
        assertNull( $e->enqueueScripts( $cb ) );
    }

    function testEnqueueScripts() {
        $cb = function() {
            return new \ArrayIterator( [ $this->getScript( 'test' ) ] );
        };
        \WP_Mock::wpFunction( 'wp_enqueue_script', [
            'args' => [ 'test', 'http://www.example.com/js/test.js', [ 'foo', 'bar' ], 1, TRUE ],
        ] );
        \WP_Mock::wpFunction( 'wp_localize_script', [
            'args'  => [ 'test', 'data_test', [ 'id' => 'test' ] ],
            'times' => 1
        ] );
        $e = new Enqueuer;
        assertTrue( $e->enqueueScripts( $cb ) );
    }

    function testEnqueueStylesNullIfNoScripts() {
        $cb = function() {
            return TRUE;
        };
        $e = new Enqueuer;
        assertNull( $e->enqueueScripts( $cb ) );
    }

    function testEnqueueStyles() {
        $cb = function() {
            return new \ArrayIterator( [ $this->getStyle( 'test' ) ] );
        };
        \WP_Mock::wpFunction( 'wp_enqueue_style', [
            'args' => [ 'test', 'http://www.example.com/css/test.css', [ 'foo', 'bar' ], 1, 'all' ],
        ] );
        $e = new Enqueuer;
        assertTrue( $e->enqueueStyles( $cb ) );
    }

    function testRegisterProvidedNullIfNotDoingHead() {
        $e = new Enqueuer;
        \WP_Mock::wpFunction( 'doing_action', [
            'args'   => [ 'wp_head' ],
            'times'  => 1,
            'return' => FALSE
        ] );
        assertNull( $e->registerProvided() );
    }

    function testRegisterProvided() {
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->shouldReceive( 'getProvided' )->with( 'styles' )->andReturn( [ 'a', 'b', 'c' ] );
        $e->shouldReceive( 'getProvided' )->with( 'scripts' )->andReturn( [ 'd', 'e' ] );
        \WP_Mock::wpFunction( 'doing_action', [
            'args'   => [ 'wp_head' ],
            'times'  => 1,
            'return' => TRUE
        ] );
        global $wp_scripts, $wp_styles;
        $wp_styles->to_do = [ 'a' ];
        $wp_scripts->to_do = [ 'd', 'z' ];
        assertTrue( $e->registerProvided() );
        assertSame( $wp_styles->to_do, [ ] );
        assertSame( $wp_styles->done, [ 'a', 'b', 'c' ] );
        assertSame( $wp_scripts->to_do, [ 'z' ] );
        assertSame( $wp_scripts->done, [ 'd', 'e' ] );
    }

}