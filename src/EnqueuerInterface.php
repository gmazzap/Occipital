<?php namespace Brain\Occipital;

interface EnqueuerInterface {

    /**
     * Enqueue assets using giving closure factories
     *
     * @param \Closure $assets_factory  Factory for assets, have to return an iterator
     */
    public function enqueue( \Closure $assets_factory );
}