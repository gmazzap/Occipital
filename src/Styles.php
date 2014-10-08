<?php namespace Brain\Occipital;

class Styles extends \WP_Styles {

    static function buildFromWp( \WP_Styles $styles ) {
        $class = get_called_class();
        $obj = new $class;
        foreach ( get_object_vars( $styles ) as $n => $v ) {
            $obj->$n = $v;
        }
        return $obj;
    }

    public function do_item( $handle, $group = false ) {
        do_action( 'brain_doing_style', $handle );
        $done = parent::do_item( $handle, $group );
        do_action( 'brain_style_done', $handle, $done );
    }

}