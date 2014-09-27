<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Container;

class ContainerTest extends TestCase {

    /**
     * @expectedException \UnexpectedValueException
     */
    function testAddScriptFailsIfBadSide() {
        $script = \Mockery::mock( 'Brain\Occipital\ScriptInterface' );
        $cont = new Container;
        $cont->addScript( $script, 32 );
    }

}