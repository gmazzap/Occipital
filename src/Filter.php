<?php namespace Brain\Occipital;

class Filter extends \FilterIterator implements FilterInterface {

    private $side;
    private $admin_page;

    public function __construct( \SplObjectStorage $storage, $side, $page = NULL ) {
        parent::__construct( $storage );
        $this->side = $side;
        $this->admin_page = $page;
    }

    public function accept() {
        /** @var \Brain\Occipital\Enqueuable $asset */
        $asset = $this->getInnerIterator()->current();
        $condition = $asset->getCondition();
        if ( empty( $condition ) ) {
            return TRUE;
        }
        if ( ! is_callable( $condition ) ) {
            throw new \UnexpectedValueException;
        }
        return call_user_func_array( $condition, $this->getConditionArgs() );
    }

    public function getConditionArgs() {
        $context = FALSE;
        $logged = FALSE;
        if ( $this->side === Container::ADMIN ) {
            $context = $this->admin_page;
            $logged = TRUE;
        }
        if ( $this->side === Container::FRONT ) {
            $context = $GLOBALS[ 'wp_query' ];
            $logged = is_user_logged_in();
        }
        return [ $this->side, $context, $GLOBALS[ 'wp_styles' ], $GLOBALS[ 'wp_scripts' ], $logged ];
    }

}