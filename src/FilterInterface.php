<?php namespace Brain\Occipital;

interface FilterInterface {

    /**
     * Get the arguments to pass to script condition callable.
     *
     * @return array
     */
    public function getConditionArgs();
}