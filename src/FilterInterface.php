<?php namespace Brain\Occipital;

interface FilterInterface extends \OuterIterator {

    /**
     * Get the arguments to pass to script condition callable.
     *
     * @return array
     */
    public function getConditionArgs();

    public function accept();
}