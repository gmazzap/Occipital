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

    function testTest() {
        assertTrue( TRUE );
    }

}