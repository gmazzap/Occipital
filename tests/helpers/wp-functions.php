<?php
if ( ! function_exists( 'wp_enqueue_asset' ) ) {

    function wp_enqueue_asset( $global, $fargs, $css = FALSE ) {
        $keys = [ 'handle', 'src', 'deps', 'ver', 'args' ];
        $args = count( $fargs ) === count( $keys ) ? array_combine( $keys, $fargs ) : $fargs;
        $handle = array_shift( $args );
        if ( $css && ! isset( $global->registered[ $handle ] ) ) {
            $handle = $handle . '-css';
        }
        $args = array_filter( array_merge( [ 'handle' => $handle ], $args ) );
        if ( ! isset( $global->registered[ $handle ] ) ) {
            $global->registered[ $handle ] = $args;
        }
        if ( ! isset( $global->queue[ $handle ] ) ) {
            $global->queue[ $handle ] = $args;
        }
    }

}

if ( ! function_exists( 'wp_asset_is' ) ) {

    function wp_asset_is( $global, $handle, $what ) {
        if ( $what === 'enqueue' ) {
            $what = 'queue';
        }
        return isset( $global->$what ) && array_key_exists( $handle, (array) $global->$what );
    }

}

if ( ! function_exists( 'wp_enqueue_script' ) ) {

    function wp_enqueue_script() {
        global $wp_scripts;
        if ( ! $wp_scripts instanceof WP_Scripts ) {
            $wp_scripts = new WP_Scripts;
        }
        wp_enqueue_asset( $wp_scripts, func_get_args() );
    }

}

if ( ! function_exists( 'wp_enqueue_style' ) ) {

    function wp_enqueue_style() {
        global $wp_styles;
        if ( ! $wp_styles instanceof WP_Styles ) {
            $wp_styles = new WP_Styles;
        }
        wp_enqueue_asset( $wp_styles, func_get_args(), TRUE );
    }

}

if ( ! function_exists( 'wp_script_is' ) ) {

    function wp_script_is( $handle, $what = 'queue' ) {
        global $wp_scripts;
        if ( ! $wp_scripts instanceof WP_Scripts ) {
            return FALSE;
        }
        return wp_asset_is( $wp_scripts, $handle, $what );
    }

}

if ( ! function_exists( 'wp_style_is' ) ) {

    function wp_style_is( $handle, $what = 'queue' ) {
        global $wp_styles;
        if ( ! $wp_styles instanceof WP_Styles ) {
            return FALSE;
        }
        return wp_asset_is( $wp_styles, $handle, $what );
    }

}
