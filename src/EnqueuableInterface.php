<?php namespace Brain\Lobe;

interface EnqueuableInterface {

    public function getHandle();

    public function setHandle( $handle );

    public function getSrc();

    public function setSrc( $src );

    public function getVer();

    public function setVer( $ver = NULL );

    public function getDeps();

    public function setDeps( Array $dependencies = [ ] );

    public function getCondition();

    public function setCondition( $condition );

    public function getProvide();

    public function setProvide( $provided );
}