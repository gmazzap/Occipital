<?php namespace Brain\Occipital\Tests;

class FilterTest extends TestCase {

    function testAcceptReturnsTrueIfNoCondition() {
        $asset = \Mockery::mock( 'Brain\Occipital\EnqueuableInterface' );
        $asset->shouldReceive( 'getCondition' )->andReturnNull();
        $filter = \Mockery::mock( 'Brain\Occipital\Filter' )->makePartial();
        $filter->shouldReceive( 'getInnerIterator->current' )->andReturn( $asset );
        assertTrue( $filter->accept() );
    }

    function testAcceptReturnsFalseIfBadCallable() {
        $asset = \Mockery::mock( 'Brain\Occipital\Enqueuable' );
        $asset->shouldReceive( 'getCondition' )->andReturn( TRUE );
        $filter = \Mockery::mock( 'Brain\Occipital\Filter' )->makePartial();
        $filter->shouldReceive( 'getInnerIterator->current' )->andReturn( $asset );
        assertFalse( $filter->accept() );
    }

    function testAcceptReturnsTrueIfConditionIsTrue() {
        $cb = function() {
            return TRUE;
        };
        $asset = \Mockery::mock( 'Brain\Occipital\Enqueuable' );
        $asset->shouldReceive( 'getCondition' )->andReturn( $cb );
        $filter = \Mockery::mock( 'Brain\Occipital\Filter' )->makePartial();
        $filter->shouldReceive( 'getInnerIterator->current' )->andReturn( $asset );
        $filter->shouldReceive( 'getConditionArgs' )->once()->andReturn( [ ] );
        assertTrue( $filter->accept() );
    }

    function testAcceptReturnsFalseIfConditionIsFalse() {
        $cb = function() {
            return FALSE;
        };
        $asset = \Mockery::mock( 'Brain\Occipital\Enqueuable' );
        $asset->shouldReceive( 'getCondition' )->andReturn( $cb );
        $filter = \Mockery::mock( 'Brain\Occipital\Filter' )->makePartial();
        $filter->shouldReceive( 'getInnerIterator->current' )->andReturn( $asset );
        $filter->shouldReceive( 'getConditionArgs' )->once()->andReturn( [ ] );
        assertFalse( $filter->accept() );
    }

}