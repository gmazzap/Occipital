<?php
if ( ! function_exists( 'wp_enqueue_script' ) ) {

    function wp_enqueue_script( $handle ) {
        global $wp_scripts;
        if ( ! $wp_scripts instanceof WP_Scripts ) {
            $wp_scripts = new WP_Scripts;
        }
        $keys = [ 'handle', 'src', 'deps', 'ver', 'args' ];
        $args = array_combine( $keys, func_get_args() );
        $wp_scripts->registered[ $handle ] = $args;
        $wp_scripts->queue[ $handle ] = $args;
    }

}
if ( ! function_exists( 'wp_enqueue_style' ) ) {

    function wp_enqueue_style( $handle ) {
        global $wp_styles;
        if ( ! $wp_styles instanceof WP_Styles ) {
            $wp_styles = new WP_Styles;
        }
        $keys = [ 'handle', 'src', 'deps', 'ver', 'args' ];
        $args = array_combine( $keys, func_get_args() );
        $wp_styles->registered[ $handle ] = $args;
        $wp_styles->queue[ $handle ] = $args;
    }

}
if ( ! function_exists( 'wp_script_is' ) ) {

    function wp_script_is( $handle, $what = 'queue' ) {
        global $wp_scripts;
        if ( ! $wp_scripts instanceof WP_Styles ) {
            return FALSE;
        }
        if ( $what === 'enqueue' ) {
            $what = 'queue';
        }
        return array_key_exists( $handle, $wp_scripts->$what );
    }

}

if ( ! function_exists( 'wp_style_is' ) ) {

    function wp_style_is( $handle, $what = 'queue' ) {
        global $wp_styles;
        if ( ! $wp_styles instanceof WP_Styles ) {
            return FALSE;
        }
        if ( $what === 'enqueue' ) {
            $what = 'queue';
        }
        return array_key_exists( $handle, $wp_styles->$what );
    }

}
