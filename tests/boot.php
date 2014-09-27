<?php
if ( ! defined( 'OCCIPITALBASEPATH' ) ) {
    define( 'OCCIPITALBASEPATH', dirname( dirname( __FILE__ ) ) );
}

$autoload = require_once OCCIPITALBASEPATH . '/vendor/autoload.php';

require_once OCCIPITALBASEPATH . '/vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

if ( ! class_exists( 'WP_Error' ) ) require_once __DIR__ . '/class-wp-error.php';

