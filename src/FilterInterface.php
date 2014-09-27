<?php namespace Brain\Lobe;

interface FilterInterface {

    /**
     * Get the arguments to pass to script condition callable.
     *
     * @return array
     */
    public function getConditionArgs();
}