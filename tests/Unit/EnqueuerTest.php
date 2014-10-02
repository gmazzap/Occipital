<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Enqueuer;

class EnqueuerTest extends TestCase {

    private function getStyle( $id = '' ) {
        if ( ! $id ) {
            $id = uniqid( 'style_' );
        }
        $style = \Mockery::mock( 'Brain\Occipital\StyleInterface' );
        $style->shouldReceive( 'getHandle' )->andReturn( $id );
        $style->shouldReceive( 'getSrc' )->andReturn( "http://www.example.com/css/{$id}.css" );
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

    function testSetupStyles() {
        $e = new Enqueuer;
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $cl_styles = function() {
            return new \ArrayIterator( [
                'foo' => $this->getStyle( 'foo' ),
                'bar' => $this->getStyle( 'bar' ),
                'baz' => $this->getStyle( 'baz' )
                ] );
        };
        $cl_scripts = function() {
            return;
        };
        $style_args = function( $id) {
            return [ $id, "http://www.example.com/css/{$id}.css", [ 'foo', 'bar' ], 1, 'all' ];
        };
        $styles = [ $style_args( 'foo' ), $style_args( 'bar' ), $style_args( 'baz' ) ];
        $provided_styles = [ 'white', "prov_by_foo", "prov_by_bar", "prov_by_baz" ];
        assertTrue( $e->setup( $cl_styles, $cl_scripts ) );
        assertSame( $styles, $e->getStyles() );
        assertSame( $provided_styles, $e->getProvidedStyles() );
    }

    function testSetupScripts() {
        $e = new Enqueuer;
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $cl_styles = function() {
            return;
        };
        $cl_scripts = function() {
            return new \ArrayIterator( [
                'foo' => $this->getScript( 'foo' ),
                'bar' => $this->getScript( 'bar' ),
                'baz' => $this->getScript( 'baz' )
                ] );
        };
        $script_args = function( $id) {
            return [ $id, "http://www.example.com/js/{$id}.js", [ 'foo', 'bar' ], 1, TRUE ];
        };
        $scripts = [ $script_args( 'foo' ), $script_args( 'bar' ), $script_args( 'baz' ) ];
        $provided_scripts = [ 'grey', "prov_by_foo", "prov_by_bar", "prov_by_baz" ];
        $data = [
            'foo' => [ 'foo', 'data_foo', [ 'id' => 'foo' ] ],
            'bar' => [ 'bar', 'data_bar', [ 'id' => 'bar' ] ],
            'baz' => [ 'baz', 'data_baz', [ 'id' => 'baz' ] ]
        ];
        assertTrue( $e->setup( $cl_styles, $cl_scripts ) );
        assertSame( $scripts, $e->getScripts() );
        assertSame( $provided_scripts, $e->getProvidedScripts() );
        assertSame( $data, $e->getContext( 'context', 'scripts_data' ) );
    }

    function testSetupEnsureStylesDeps() {
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->__construct();
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $cl = function() {
            return;
        };
        global $wp_styles, $wp_scripts;
        $wp_styles->registered = [
            'x'   => (object) [ 'deps' => [ ] ],
            'z'   => (object) [ 'deps' => [ ] ],
            'i'   => (object) [ 'deps' => [ ] ],
            'y'   => (object) [ 'deps' => [ 'x' ] ],
            'foo' => (object) [ 'deps' => [ 'y', 'z' ] ],
            'bar' => (object) [ 'deps' => [ 'i' ] ]
        ];
        $e->shouldReceive( 'getProvidedStyles' )->andReturn( [ 'z', 'foo', 'bar' ] );
        $e->shouldReceive( 'getProvidedScripts' )->andReturn( [ ] );
        assertTrue( $e->setup( $cl, $cl ) );
        assertSame( [ 'y', 'i' ], array_keys( $wp_styles->queue ) );
        assertSame( [ ], $wp_scripts->queue );
    }

    function testSetupEnsureScriptsDeps() {
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->__construct();
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $cl = function() {
            return;
        };
        global $wp_styles, $wp_scripts;
        $wp_scripts->registered = [
            'x'   => (object) [ 'deps' => [ ] ],
            'z'   => (object) [ 'deps' => [ ] ],
            'i'   => (object) [ 'deps' => [ ] ],
            'y'   => (object) [ 'deps' => [ 'x' ] ],
            'foo' => (object) [ 'deps' => [ 'y', 'z' ] ],
            'bar' => (object) [ 'deps' => [ 'i' ] ]
        ];
        $e->shouldReceive( 'getProvidedStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedScripts' )->andReturn( [ 'z', 'foo', 'bar' ] );
        assertTrue( $e->setup( $cl, $cl ) );
        assertSame( [ 'y', 'i' ], array_keys( $wp_scripts->queue ) );
        assertSame( [ ], $wp_styles->queue );
    }

    function testEnqueueDoNothingIfNothingtoDo() {
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->__construct();
        $e->shouldReceive( 'getStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getScripts' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedScripts' )->andReturn( [ ] );
        assertSame( array_fill( 0, 4, FALSE ), array_values( $e->enqueue() ) );
    }

    function testEnqueueStyles() {
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->__construct();
        $keys = [ 'handle', 'src', 'deps', 'ver', 'args' ];
        $style_args = function( $id ) {
            return [ $id, "http://www.example.com/css/{$id}.css", [ 'foo', 'bar' ], 1, 'all' ];
        };
        $styles = [ $style_args( 'foo' ), $style_args( 'bar' ), $style_args( 'baz' ) ];
        $e->shouldReceive( 'getStyles' )->andReturn( $styles );
        $e->shouldReceive( 'getScripts' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedScripts' )->andReturn( [ ] );
        global $wp_styles;
        $queue = [
            'foo' => array_combine( $keys, $style_args( 'foo' ) ),
            'bar' => array_combine( $keys, $style_args( 'bar' ) ),
            'baz' => array_combine( $keys, $style_args( 'baz' ) )
        ];
        $done = $e->enqueue();
        assertTrue( $done[ 'styles_enqueue' ] );
        assertSame( $queue, $wp_styles->registered );
        assertSame( $queue, $wp_styles->queue );
    }

    function testEnqueueScripts() {
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->__construct();
        $keys = [ 'handle', 'src', 'deps', 'ver', 'args' ];
        $script_args = function( $id ) {
            return [ $id, "http://www.example.com/js/{$id}.js", [ 'foo', 'bar' ], 1, TRUE ];
        };
        $scripts = [ $script_args( 'foo' ), $script_args( 'bar' ), $script_args( 'baz' ) ];
        $data = [
            'foo' => [ 'test', 'Test', [ 'test' ] ],
            'bar' => [ 'test', 'Test', [ 'test' ] ],
            'baz' => [ 'test', 'Test', [ 'test' ] ]
        ];
        $e->shouldReceive( 'getStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getScripts' )->andReturn( $scripts );
        $e->shouldReceive( 'getContext' )->with( 'context', 'scripts_data' )->andReturn( $data );
        $e->shouldReceive( 'getProvidedStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedScripts' )->andReturn( [ ] );
        global $wp_scripts;
        $queue = [
            'foo' => array_combine( $keys, $script_args( 'foo' ) ),
            'bar' => array_combine( $keys, $script_args( 'bar' ) ),
            'baz' => array_combine( $keys, $script_args( 'baz' ) )
        ];
        \WP_Mock::wpFunction( 'wp_localize_script', [
            'args'  => [ 'test', 'Test', [ 'test' ] ],
            'times' => 3
        ] );
        $done = $e->enqueue();
        assertTrue( $done[ 'scripts_enqueue' ] );
        assertSame( $queue, $wp_scripts->registered );
        assertSame( $queue, $wp_scripts->queue );
    }

    function testEnqueueProvidedStyles() {
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->__construct();
        $provided_styles = [ 'grey', 'old', 'red' ];
        $e->shouldReceive( 'getStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getScripts' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedStyles' )->andReturn( $provided_styles );
        $e->shouldReceive( 'getProvidedScripts' )->andReturn( [ ] );
        global $wp_styles;
        $wp_styles->to_do = [ 'white', 'grey', 'blue' ];
        $wp_styles->done = [ 'old' ];
        $done = $e->enqueue();
        assertTrue( $done[ 'styles_provide' ] );
        assertSame( [ 'white', 'blue' ], $wp_styles->to_do );
        assertSame( [ 'old', 'grey', 'red' ], $wp_styles->done );
    }

    function testEnqueueProvidedScripts() {
        \WP_Mock::wpFunction( 'current_filter', [
            'return' => 'brain_assets_done'
        ] );
        \WP_Mock::wpFunction( 'remove_action', [
            'args' => [ \WP_Mock\Functions::type( 'string' ), \WP_Mock\Functions::type( 'array' ) ]
        ] );
        $e = \Mockery::mock( 'Brain\Occipital\Enqueuer' )->makePartial();
        $e->__construct();
        $provided_scripts = [ 'grey', 'old', 'red' ];
        $e->shouldReceive( 'getStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getScripts' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedStyles' )->andReturn( [ ] );
        $e->shouldReceive( 'getProvidedScripts' )->andReturn( $provided_scripts );
        global $wp_scripts;
        $wp_scripts->to_do = [ 'white', 'grey', 'blue' ];
        $wp_scripts->done = [ 'old' ];
        $done = $e->enqueue();
        assertTrue( $done[ 'scripts_provide' ] );
        assertSame( [ 'white', 'blue' ], $wp_scripts->to_do );
        assertSame( [ 'old', 'grey', 'red' ], $wp_scripts->done );
    }

}