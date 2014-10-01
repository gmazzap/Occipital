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
        $scripts = new \SplObjectStorage;
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $c = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $c->shouldReceive( 'getSide' )->withNoArgs()->andReturnNull();
        $c->shouldReceive( 'getScripts' )->with( Container::ALL )->once()->andReturn( $scripts );
        assertSame( $script, $c->addScript( $script ) );
        assertTrue( $scripts->contains( $script ) );
    }

    function testAddGivenSideOnSameSide() {
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_done' ],
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
            'args'   => [ 'brain_assets_done' ],
            'return' => FALSE
        ] );
        \WP_Mock::wpFunction( 'did_action', [
            'args'   => [ 'brain_assets_ready' ],
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
            'args'   => [ 'brain_assets_done' ],
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

    /**
     * @expectedException RuntimeException
     */
    function testGetSideStylesFailsIfNotSide() {
        $cont = new Container;
        $cont->getSideStyles();
    }

    function testGetSideStyles() {
        $cont = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $cont->shouldReceive( 'getSide' )->andReturn( Container::ADMIN );
        $side = new \SplObjectStorage;
        $all = new \SplObjectStorage;
        $a = (object) [ 'side' => 'admin' ];
        $b = (object) [ 'side' => 'all' ];
        $side->attach( $a );
        $all->attach( $b );
        $cont->shouldReceive( 'getStyles' )->with( Container::ADMIN )->andReturn( $side );
        $cont->shouldReceive( 'getStyles' )->with( Container::ALL )->andReturn( $all );
        $test = $cont->getSideStyles();
        assertInstanceOf( 'SplObjectStorage', $test );
        assertCount( 2, $test );
        foreach ( $test as $object ) {
            assertObjectHasAttribute( 'side', $object );
            assertTrue( in_array( $object->side, [ 'admin', 'all' ], TRUE ) );
        }
    }

    /**
     * @expectedException RuntimeException
     */
    function testGetSideScriptsFailsIfNotSide() {
        $cont = new Container;
        $cont->getSideScripts();
    }

    function testGetSideScripts() {
        $cont = \Mockery::mock( 'Brain\Occipital\Container' )->makePartial();
        $cont->shouldReceive( 'getSide' )->andReturn( Container::FRONT );
        $side = new \SplObjectStorage;
        $all = new \SplObjectStorage;
        $a = (object) [ 'side' => 'front' ];
        $b = (object) [ 'side' => 'all' ];
        $side->attach( $a );
        $all->attach( $b );
        $cont->shouldReceive( 'getScripts' )->with( Container::FRONT )->andReturn( $side );
        $cont->shouldReceive( 'getScripts' )->with( Container::ALL )->andReturn( $all );
        $test = $cont->getSideScripts();
        assertInstanceOf( 'SplObjectStorage', $test );
        assertCount( 2, $test );
        foreach ( $test as $object ) {
            assertObjectHasAttribute( 'side', $object );
            assertTrue( in_array( $object->side, [ 'front', 'all' ], TRUE ) );
        }
    }

}