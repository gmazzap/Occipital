<?php namespace Brain;

class Occipital {

    public static function boot() {
        if ( ! function_exists( 'add_action' ) ) {
            return;
        }
        add_action( 'brain_init', function( $brain ) {
            $brain->addModule( new Occipital\BrainModule );
        } );
    }

}