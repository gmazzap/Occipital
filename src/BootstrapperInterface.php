<?php namespace Brain\Occipital;

interface BootstrapperInterface {

    /**
     * Boot pacakage
     */
    public function boot();

    /**
     * Get Container instance
     *
     * @return \Brain\Occipital\Container
     */
    public function getContainer();

    /**
     * Get current side
     *
     * @return int|void One of the sides constances or NULL if called to early
     */
    public function getSide();
}