<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Container;

class ContainerTest extends TestCase {

    /**
     * @expectedException UnexpectedValueException
     */
    function testAddFailsIfBadGivenSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $c = new Container;
        $c->addScript( $script, -1 );
    }

    /**
     * @expectedException UnexpectedValueException
     */
    function testAddFailsIfLateNotGiven() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => TRUE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $c = new Container;
        $c->addScript( $script );
    }

    /**
     * @expectedException UnexpectedValueException
     */
    function testAddFailsIfLateGiven() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => TRUE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $c = new Container;
        $c->addScript( $script, Container::ALL );
    }

    /**
     * @expectedException UnexpectedValueException
     */
    function testAddFailsIfGivenIsNotCurrentSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => FALSE
        ] );
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_ready' ],
            'return' => TRUE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturn( Container::FRONT );
        $c->addScript( $script, Container::ADMIN );
    }

    function testAddAllIfNotGivenAndEarly() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => FALSE
        ] );
        $scripts = new \ArrayIterator;
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $script->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturnNull();
        $c->shouldReceive( 'getScripts' )->with( Container::ALL )->once()->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script ) );
        assertTrue( $scripts->offsetExists( 'foo' ) );
    }

    function testAddGivenSideOnSameSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $script->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $scripts = new \ArrayIterator;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturn( Container::FRONT );
        $c->shouldReceive( 'getScripts' )->with( Container::FRONT )->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script, Container::FRONT ) );
        assertTrue( $scripts->offsetExists( 'foo' ) );
    }

    function testAddGivenSideEarly() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => FALSE
        ] );
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_ready' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $script->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $scripts = new \ArrayIterator;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturnNull();
        $c->shouldReceive( 'getScripts' )->with( Container::FRONT )->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script, Container::FRONT ) );
        assertTrue( $scripts->offsetExists( 'foo' ) );
    }

    function testAddNotGivenSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $script->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $scripts = new \ArrayIterator;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturn( Container::FRONT );
        $c->shouldReceive( 'getScripts' )->with( Container::FRONT )->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script ) );
        assertTrue( $scripts->offsetExists( 'foo' ) );
    }

    function testRemoveScriptByObject() {
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $script->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $scripts = new \ArrayIterator;
        $scripts[ 'foo' ] = $script;
        \WP_Mock::wpFunction( 'wp_dequeue_script', [
            'args'  => [ 'foo' ],
            'times' => 1
        ] );
        global $wp_scripts;
        $wp_scripts->queue[ 'foo' ] = new \stdClass;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSideScripts' )->withNoArgs()->andReturn( $scripts );
        $c->removeScript( $script );
        assertFalse( isset( $scripts[ 'foo' ] ) );
    }

    function testRemoveScriptByHandle() {
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $script->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $scripts = new \ArrayIterator;
        $scripts[ 'foo' ] = $script;
        \WP_Mock::wpFunction( 'wp_dequeue_script', [
            'args'  => [ 'foo' ],
            'times' => 1
        ] );
        global $wp_scripts;
        $wp_scripts->queue[ 'foo' ] = new \stdClass;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSideScripts' )->withNoArgs()->andReturn( $scripts );
        $c->removeScript( 'foo' );
        assertFalse( isset( $scripts[ 'foo' ] ) );
    }

    function testRemoveStyleByObject() {
        $style = \Mockery::mock( 'Brain\Occipital\StyleInterface' );
        $style->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $styles = new \ArrayIterator;
        $styles[ 'foo' ] = $style;
        \WP_Mock::wpFunction( 'wp_dequeue_style', [
            'args'  => [ 'foo' ],
            'times' => 1
        ] );
        global $wp_styles;
        $wp_styles->queue[ 'foo' ] = new \stdClass;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSideStyles' )->withNoArgs()->andReturn( $styles );
        $c->removeStyle( $style );
        assertFalse( isset( $styles[ 'foo' ] ) );
    }

    function testRemoveStyleByHandle() {
        $style = \Mockery::mock( 'Brain\Occipital\StyleInterface' );
        $style->shouldReceive( 'getHandle' )->andReturn( 'foo' );
        $styles = new \ArrayIterator;
        $styles[ 'foo' ] = $style;
        \WP_Mock::wpFunction( 'wp_dequeue_style', [
            'args'  => [ 'foo' ],
            'times' => 1
        ] );
        global $wp_styles;
        $wp_styles->queue[ 'foo' ] = new \stdClass;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSideStyles' )->withNoArgs()->andReturn( $styles );
        $c->removeStyle( 'foo' );
        assertFalse( isset( $styles[ 'foo' ] ) );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testGetScriptsFailsIfBadSide() {
        $cont = new Container;
        $cont->getScripts( 123456789 );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testGetStylesFailsIfBadSide() {
        $cont = new Container;
        $cont->getStyles( 123456789 );
    }

    function testGetScriptsArray() {
        $cont = new Container;
        $scripts = $cont->getScripts();
        assertInternalType( 'array', $scripts );
        assertArrayHasKey( Container::ALL, $scripts );
        assertInstanceOf( 'ArrayIterator', array_shift( $scripts ) );
    }

    function testGetScripts() {
        $cont = new Container;
        assertInstanceOf( 'ArrayIterator', $cont->getScripts( Container::ALL ) );
    }

    function testGetStylesArray() {
        $cont = new Container;
        $styles = $cont->getStyles();
        assertInternalType( 'array', $styles );
        assertArrayHasKey( Container::ALL, $styles );
        assertInstanceOf( 'ArrayIterator', array_shift( $styles ) );
    }

    function testGetStyles() {
        $cont = new Container;
        assertInstanceOf( 'ArrayIterator', $cont->getStyles( Container::ALL ) );
    }

    function testGetSideStylesNullIfNotSide() {
        $cont = new Container;
        assertNull( $cont->getSideStyles() );
    }

    function testGetSideScriptsNullIfNotSide() {
        $cont = new Container;
        assertNull( $cont->getSideScripts() );
    }

}