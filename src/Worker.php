<?php namespace Brain\Occipital;

class Worker {

    private $enqueuer;
    private $scripts;
    private $styles;
    private $done = FALSE;

    function __construct( EnqueuerInterface $enqueuer, \Closure $scripts, \Closure $styles ) {
        $this->enqueuer = $enqueuer;
        $this->scripts = $scripts;
        $this->styles = $styles;
    }

    function work() {
        if ( current_filter() !== 'lobe_done' || $this->done ) {
            return FALSE;
        }
        $done = 0;
        /** @var \Brain\Occipital\Filter $scripts */
        $scripts = $this->scripts->__invoke();
        /** @var $scripts \Brain\Occipital\Filter */
        $styles = $this->styles->__invoke();
        if ( $styles instanceof FilterInterface ) {
            $this->enqueuer->enqueueStyles( $styles );
            $done ++;
        }
        if ( $scripts instanceof FilterInterface ) {
            $this->enqueuer->enqueueScripts( $scripts );
            $done ++;
        }
        if ( $done == TRUE ) {
            $this->enqueuer->registerProvided();
            $this->done = TRUE;
        }
        return $this->done;
    }

}