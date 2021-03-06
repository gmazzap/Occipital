<?php namespace Brain\Occipital;

interface EnqueuableInterface {

    const SCRIPT = 101;
    const STYLE = 202;

    /**
     * Fill all properties form WP registered asset
     */
    public function fillFromRegistered();

    /**
     * Getter for the asset handle.
     */
    public function getHandle();

    /**
     * Setter for the asset handle.
     *
     * @param string $handle
     */
    public function setHandle( $handle );

    /**
     * Getter for the asset url.
     */
    public function getSrc();

    /**
     * Setter for the asset url.
     *
     * @param string $src
     */
    public function setSrc( $src );

    /**
     * Getter for the asset version
     */
    public function getVer();

    /**
     * Setter for the asset handle
     *
     * @param string|int|NULL $ver
     */
    public function setVer( $ver = NULL );

    /**
     * Getter for the asset dependencies
     */
    public function getDeps();

    /**
     * Setter for the asset dependencies
     *
     * @param array $dependencies
     */
    public function setDeps( Array $dependencies = [ ] );

    /**
     * Getter for the asset condition callable
     */
    public function getCondition();

    /**
     * Setter for the asset condition callable
     *
     * @param callable $condition
     */
    public function setCondition( $condition );

    /**
     * Getter for other assets current asset provides
     */
    public function getProvided();

    /**
     * Setter for other assets current asset provides
     *
     * @param string|array $provided
     */
    public function setProvided( $provided );
}