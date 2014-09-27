<?php
if ( ! function_exists( 'wp_enqueue_script' ) ) {

    function wp_enqueue_script( $handle ) {
        global $wp_scripts;
        if ( ! $wp_scripts instanceof WP_Scripts ) {
            $wp_scripts = new WP_Scripts;
        }
        $wp_scripts->queue[ $handle ] = func_get_args();
    }

}
if ( ! function_exists( 'wp_enqueue_style' ) ) {

    function wp_enqueue_style( $handle ) {
        global $wp_styles;
        if ( ! $wp_styles instanceof WP_Styles ) {
            $wp_styles = new WP_Styles;
        }
        $wp_styles->queue[ $handle ] = func_get_args();
    }

}
