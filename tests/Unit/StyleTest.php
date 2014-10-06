<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Style;

class StyleTest extends TestCase {

    function testFillFromRegistered() {
        $args = [
            'handle' => 'foo',
            'src'    => 'path/to/foo',
            'deps'   => [ ],
            'ver'    => NULL,
            'args'   => 'all',
            'extra'  => [ ]
        ];
        $style = \Mockery::mock( '_WP_Dependency' );
        foreach ( $args as $key => $arg ) {
            $style->$key = $arg;
        }
        $GLOBALS[ 'wp_styles' ]->registered[ 'foo' ] = $style;
        $s = new Style( 'foo' );
        $s->fillFromRegistered();
        assertSame( $s->getSrc(), 'path/to/foo' );
        assertSame( $s->getDeps(), [ ] );
        assertSame( 'all', $s->getMedia() );
        assertSame( [ ], $s->getAfter() );
    }

    function testFillFromRegisteredExtra() {
        $args = [
            'handle' => 'foo',
            'src'    => 'path/to/foo',
            'deps'   => [ 'bar', 'baz' ],
            'ver'    => NULL,
            'args'   => 'screen',
            'extra'  => [
                'after' => [
                    '.foo { display: none; }',
                    '.bar { display: block; }'
                ]
            ]
        ];
        $script = \Mockery::mock( '_WP_Dependency' );
        foreach ( $args as $key => $arg ) {
            $script->$key = $arg;
        }
        $GLOBALS[ 'wp_styles' ]->registered[ 'foo' ] = $script;
        $s = new Style( 'foo' );
        $s->fillFromRegistered();
        assertSame( $s->getSrc(), 'path/to/foo' );
        assertSame( $s->getDeps(), [ 'bar', 'baz' ] );
        assertSame( 'screen', $s->getMedia() );
        assertSame( $s->getAfter(), $args[ 'extra' ][ 'after' ] );
    }

}