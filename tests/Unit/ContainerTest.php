<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Container;

class ContainerTest extends TestCase {

    /**
     * @expectedException UnexpectedValueException
     */
    function testAddFailsIfBadGivenSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'lobe_done' ],
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
            'args'   => [ 'lobe_done' ],
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
            'args'   => [ 'lobe_done' ],
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
            'args'   => [ 'lobe_done' ],
            'return' => FALSE
        ] );
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'lobe_ready' ],
            'return' => TRUE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturn( Container::FRONT );
        $c->addScript( $script, Container::ADMIN );
    }

    /**
     * @expectedException UnexpectedValueException
     */
    function testAddFailsIfNotGivenAndEarly() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'lobe_done' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturnNull();
        $c->addScript( $script );
    }

    function testAddGivenSideOnSameSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'lobe_done' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $scripts = new \SplObjectStorage;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturn( Container::FRONT );
        $c->shouldReceive( 'getScripts' )->with( Container::FRONT )->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script, Container::FRONT ) );
        assertTrue( $scripts->contains( $script ) );
    }

    function testAddGivenSideEarly() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'lobe_done' ],
            'return' => FALSE
        ] );
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'lobe_ready' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $scripts = new \SplObjectStorage;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturnNull();
        $c->shouldReceive( 'getScripts' )->with( Container::FRONT )->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script, Container::FRONT ) );
        assertTrue( $scripts->contains( $script ) );
    }

    function testAddNotGivenSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'lobe_done' ],
            'return' => FALSE
        ] );
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $scripts = new \SplObjectStorage;
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturn( Container::FRONT );
        $c->shouldReceive( 'getScripts' )->with( Container::FRONT )->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script ) );
        assertTrue( $scripts->contains( $script ) );
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
        assertInstanceOf( 'SplObjectStorage', array_shift( $scripts ) );
    }

    function testGetScripts() {
        $cont = new Container;
        assertInstanceOf( 'SplObjectStorage', $cont->getScripts( Container::ALL ) );
    }

    function testGetStylesArray() {
        $cont = new Container;
        $styles = $cont->getStyles();
        assertInternalType( 'array', $styles );
        assertArrayHasKey( Container::ALL, $styles );
        assertInstanceOf( 'SplObjectStorage', array_shift( $styles ) );
    }

    function testGetStyles() {
        $cont = new Container;
        assertInstanceOf( 'SplObjectStorage', $cont->getStyles( Container::ALL ) );
    }

}