<?php namespace Brain\Occipital;

class Scripts extends \WP_Scripts {

    static function buildFromWp( \WP_Scripts $scripts ) {
        $class = get_called_class();
        $obj = new $class;
        foreach ( get_object_vars( $scripts ) as $n => $v ) {
            $obj->$n = $v;
        }
        return $obj;
    }

    public function do_item( $handle, $group = false ) {
        do_action( 'brain_doing_script', $handle );
        $done = parent::do_item( $handle, $group );
        do_action( 'brain_script_done', $handle, $done );
    }

}