<?php namespace Brain\Occipital;

class Filter extends \FilterIterator implements FilterInterface {

    private $side;

    public function __construct( \Iterator $storage, $side ) {
        parent::__construct( $storage );
        $this->side = $side;
    }

    public function accept() {
        /** @var \Brain\Occipital\Enqueuable $asset */
        $asset = $this->getInnerIterator()->current();
        $condition = $asset->getCondition();
        if ( empty( $condition ) ) {
            return TRUE;
        }
        if ( ! is_callable( $condition ) ) {
            return FALSE;
        }
        $result = call_user_func_array( $condition, $this->getConditionArgs() );
        return ! empty( $result );
    }

    public function getConditionArgs() {
        $context = FALSE;
        $logged = FALSE;
        if ( $this->side === Container::ADMIN ) {
            $context = function_exists( 'get_current_screen' ) ? get_current_screen() : FALSE;
            $logged = wp_get_current_user();
        }
        if ( $this->side === Container::FRONT ) {
            $context = $GLOBALS[ 'wp_query' ];
            $logged = is_user_logged_in() ? wp_get_current_user() : FALSE;
        }
        return [ $context, $this->side, $logged ];
    }

}