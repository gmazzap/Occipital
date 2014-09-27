<?php
if ( ! defined( 'LOBEBASEPATH' ) ) define( 'LOBEBASEPATH', dirname( dirname( __FILE__ ) ) );

$autoload = require_once LOBEBASEPATH . '/vendor/autoload.php';

require_once LOBEBASEPATH . '/vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

if ( ! class_exists( 'WP_Error' ) ) require_once __DIR__ . '/class-wp-error.php';

