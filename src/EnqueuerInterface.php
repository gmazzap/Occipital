<?php namespace Brain\Occipital;

interface EnqueuerInterface {

    /**
     * Enqueue the given script objects, extracting properties and calling `wp_enqueue_script`.
     *
     * @param \SplObjectStorage $scripts_factory
     */
    public function enqueueScripts( \Closure $scripts_factory );

    /**
     * Enqueue the given script objects, extracting properties and calling `wp_enqueue_style`.
     *
     * @param \SplObjectStorage $styles_factory
     */
    public function enqueueStyles( \Closure $styles_factory );

    /**
     * Set asdone the styles and the scripts provided by registered scripts and styles.
     */
    public function registerProvided();
}