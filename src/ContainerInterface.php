<?php namespace Brain\Lobe;

interface ContainerInterface {

    const FRONT = 1;
    const ADMIN = 2;
    const LOGIN = 3;
    const ALL = 4;

    function addScript( ScriptInterface $script );

    function addStyle( StyleInterface $style );

    function getStyles();

    function getScripts();

    public function getSide();
}