<?php namespace Brain\Occipital;

interface EnqueuerInterface {

    /**
     * Enqueue the given script objects, extracting properties and calling `wp_enqueue_script`.
     *
     * @param \SplObjectStorage $scripts
     */
    public function enqueueScripts( \SplObjectStorage $scripts );

    /**
     * Enqueue the given script objects, extracting properties and calling `wp_enqueue_style`.
     *
     * @param \SplObjectStorage $styles
     */
    public function enqueueStyles( \SplObjectStorage $styles );

    /**
     * Set asdone the styles and the scripts provided by registered scripts and styles.
     */
    public function registerProvided();
}