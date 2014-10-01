<?php namespace Brain\Occipital;

interface EnqueuerInterface {

    /**
     * Get assets to enqueue using giving closure factories
     *
     * @param \Closure $styles_factory  Factory for styles, have to return an iterator
     * @param \Closure $scripts_factory Factory for scripts, have to return an iterator
     */
    public function setup( \Closure $styles_factory, \Closure $scripts_factory );

    /**
     * Enqueue scripts and styles.
     */
    public function enqueue();

    /**
     * Get enqueued styles
     *
     * @return array
     */
    public function getStyles();

    /**
     * Get enqueued styles
     *
     * @return array
     */
    public function getScripts();

    /**
     * Get provided styles
     *
     * @return array
     */
    public function getProvidedStyles();

    /**
     * Get provided scripts
     *
     * @return array
     */
    public function getProvidedScripts();

    /**
     * Get scripts localization data
     *
     * @return array
     */
    public function getScriptsData();
}