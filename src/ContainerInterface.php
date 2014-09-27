<?php namespace Brain\Lobe;

interface ContainerInterface {

    const FRONT = 1;
    const ADMIN = 2;
    const LOGIN = 3;
    const ALL = 4;

    /**
     * Add a script in the container
     *
     * @param \Brain\Lobe\ScriptInterface $script
     */
    function addScript( ScriptInterface $script );

    /**
     * Add a style in the container
     *
     * @param \Brain\Lobe\StyleInterface $style
     */
    function addStyle( StyleInterface $style );

    /**
     * Get added styles
     */
    function getStyles();

    /**
     * Get added scripts
     */
    function getScripts();

    /**
     * Return one of interface constants related to current "side": backend, frontend, or login page.
     *
     * @return int Current side constant
     */
    public function getSide();
}