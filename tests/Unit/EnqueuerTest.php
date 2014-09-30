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
        // See `helpers.php` to see how wp_enqueue_script() is mocked
        global $wp_scripts;
        $ids = [ 'a', 'b', 'c' ];
        $queue = [ ];
        $cb = function() use($ids, &$queue) {
            $scripts = [ ];
            foreach ( $ids as $id ) {
                $s = $this->getScript( $id );
                $queue[ $id ] = [ $id, $s->getSrc(), $s->getDeps(), $s->getVer(), $s->isFooter() ];
                $scripts[] = $s;
            }
            return new \ArrayIterator( $scripts );
        };
        \WP_Mock::wpFunction( 'wp_localize_script', [
            'args'  => [
                \WP_Mock\Functions::anyOf( 'a', 'b', 'c' ),
                \WP_Mock\Functions::anyOf( 'data_a', 'data_b', 'data_c' ),
                \WP_Mock\Functions::type( 'array' )
            ],
            'times' => count( $ids )
        ] );
        $e = new Enqueuer;
        assertTrue( $e->enqueueScripts( $cb ) );
        assertSame( $wp_scripts->queue, $queue );
    }

    function testEnqueueStylesNullIfNoScripts() {
        $cb = function() {
            return TRUE;
        };
        $e = new Enqueuer;
        assertNull( $e->enqueueScripts( $cb ) );
    }

    function testEnqueueStyles() {
        // See `helpers.php` to see how wp_enqueue_style() is mocked
        global $wp_styles;
        $ids = [ 'a', 'b', 'c' ];
        $queue = [ ];
        $cb = function() use($ids, &$queue) {
            $styles = [ ];
            foreach ( $ids as $id ) {
                $s = $this->getStyle( $id );
                $queue[ $id ] = [ $id, $s->getSrc(), $s->getDeps(), $s->getVer(), $s->getMedia() ];
                $styles[] = $s;
            }
            return new \ArrayIterator( $styles );
        };
        $e = new Enqueuer;
        assertTrue( $e->enqueueStyles( $cb ) );
        assertSame( $wp_styles->queue, $queue );
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