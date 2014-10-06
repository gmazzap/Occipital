<?php namespace Brain\Occipital\Tests;

use Brain\Occipital\Script;

class ScriptTest extends TestCase {

    function testLocalizeData() {
        $data = [ 'name' => 'FOO', 'data' => [ 'foo' => 'bar' ] ];
        $s = new Script( 'foo' );
        $s->setLocalizeData( $data );
        assertEquals( $s->getLocalizeData(), [ (object) $data ] );
    }

    function testLocalizeDataFromObject() {
        $data = (object) [ 'name' => 'FOO', 'data' => [ 'foo' => 'bar' ], 'foo' => 'bar' ];
        $s = new Script( 'foo' );
        $s->setLocalizeData( $data );
        assertEquals( $s->getLocalizeData(), [ (object) [ 'name' => $data->name, 'data' => $data->data ] ] );
    }

    function testLocalizeDataSanitizeVarName() {
        $data = [ 'name' => '<script>FOO</script>', 'data' => [ 'foo' => 'bar' ] ];
        $s = new Script( 'foo' );
        $s->setLocalizeData( $data );
        assertEquals( $s->getLocalizeData(), [ (object) [ 'name' => 'FOO', 'data' => $data[ 'data' ] ] ] );
    }

    function testLocalizeDataDoNothingIfBadData() {
        $data = [ 'weird' => 'FOO', 'data' => [ 'foo' => 'bar' ] ];
        $s = new Script( 'foo' );
        $s->setLocalizeData( $data );
        assertSame( [ ], $s->getLocalizeData() );
    }

    function testFillFromRegistered() {
        $args = [
            'handle' => 'foo',
            'src'    => 'path/to/foo',
            'deps'   => [ ],
            'ver'    => NULL,
            'args'   => '',
            'extra'  => [ ]
        ];
        $script = \Mockery::mock( '_WP_Dependency' );
        foreach ( $args as $key => $arg ) {
            $script->$key = $arg;
        }
        $GLOBALS[ 'wp_scripts' ]->registered[ 'foo' ] = $script;
        $s = new Script( 'foo' );
        $s->fillFromRegistered();
        assertSame( $s->getSrc(), 'path/to/foo' );
        assertSame( $s->getDeps(), [ ] );
        assertFalse( $s->isFooter() );
        assertSame( [ ], $s->getLocalizeData() );
    }

    function testFillFromRegisteredExtra() {
        $exp_data = [
            (object) [ 'name' => 'FOO', 'data' => [ 'foo' => 'bar' ] ],
            (object) [ 'name' => 'BAR', 'data' => [ 'foo', 'bar' ] ]
        ];
        $data_str = '';
        foreach ( $exp_data as $obj ) {
            if ( $data_str != '' ) $data_str .= "\n";
            $data_str .= 'var ' . $obj->name . ' = ' . json_encode( $obj->data ) . ';';
        }
        $args = [
            'handle' => 'foo',
            'src'    => 'path/to/foo',
            'deps'   => [ ],
            'ver'    => NULL,
            'args'   => '',
            'extra'  => [ 'group' => '1', 'data' => $data_str ]
        ];
        $script = \Mockery::mock( '_WP_Dependency' );
        foreach ( $args as $key => $arg ) {
            $script->$key = $arg;
        }
        $GLOBALS[ 'wp_scripts' ]->registered[ 'foo' ] = $script;
        $s = new Script( 'foo' );
        $s->fillFromRegistered();
        assertSame( $s->getSrc(), 'path/to/foo' );
        assertSame( $s->getDeps(), [ ] );
        assertTrue( $s->isFooter() );
        assertEquals( $exp_data, $s->getLocalizeData() );
    }

}