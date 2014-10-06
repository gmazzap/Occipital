<?php namespace Brain\Occipital;

interface ContainerInterface {

    const FRONT = 1;
    const ADMIN = 2;
    const LOGIN = 3;
    const ALL = 4;

    /**
     * Add a script in the container
     *
     * @param \Brain\Occipital\ScriptInterface $script
     */
    function addScript( ScriptInterface $script );

    /**
     * Add a style in the container
     *
     * @param \Brain\Occipital\StyleInterface $style
     */
    function addStyle( StyleInterface $style );

    /**
     * Remove a script from the container
     *
     * @param \Brain\Occipital\ScriptInterface|string $script Script handle or object
     */
    function removeScript( $script );

    /**
     * Add a style in the container
     *
     * @param string|\Brain\Occipital\StyleInterface $style Style handle or object
     */
    function removeStyle( $style );

    /**
     * Get added styles
     *
     * * @return \Iterator
     */
    function getStyles();

    /**
     * Get added scripts
     *
     * * @return \Iterator
     */
    function getScripts();

    /**
     * Get an added style by handle
     *
     * * @return \Brain\Occipital\StyleInterface
     */
    function getStyle( $handle );

    /**
     * Get an added script by handle
     *
     * * @return \Brain\Occipital\ScriptInterface
     */
    function getScript( $handle );

    /**
     * Return one of interface constants related to current "side": backend, frontend, or login page.
     *
     * @return int Current side constant
     */
    public function getSide();

    /**
     * Set current "side": backend, frontend, or login page.
     *
     * @param int $side Current side constant
     */
    public function setSide( $side );

    /**
     * Get added styles specific for current side
     *
     * @return \Iterator
     */
    public function getAssetsIterator();

    /**
     * Set styles iterator for current side
     */
    public function setAssetsIterator( \Iterator $assets );
}