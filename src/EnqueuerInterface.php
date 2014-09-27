<?php namespace Brain\Lobe;

interface EnqueuerInterface {

    public function enqueueScripts( \SplObjectStorage $scripts );

    public function enqueueStyles( \SplObjectStorage $styles );

    public function registerProvided();
}