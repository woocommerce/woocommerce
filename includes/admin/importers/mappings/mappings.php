<?php
/**
 * Load up extra automatic mappings for the CSV importer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include( dirname( __FILE__ ) . '/default.php' );
include( dirname( __FILE__ ) . '/generic.php' );
include( dirname( __FILE__ ) . '/wordpress.php' );
