<?php namespace Brain\Occipital;

interface FilterInterface extends \OuterIterator {

    /**
     * Get the arguments to pass to script condition callable.
     *
     * @return array
     */
    public function getConditionArgs();

    /**
     * Filter the script and styles iterator returning the ones which condition callback returs true
     *
     * @return boolean
     */
    public function accept();
}