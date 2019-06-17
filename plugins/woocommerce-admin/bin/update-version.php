<?php
/**
 * Updates PHP versions to match those in package.json before start or build.
 *
 * @package WooCommerce Admin
 */

$package_json = file_get_contents( 'package.json' );
$package      = json_decode( $package_json );
$plugin_file  = file( 'woocommerce-admin.php' );
$lines        = array();

foreach ( $plugin_file as $line ) {
	if ( stripos( $line, ' * Version: ' ) !== false ) {
		$line = " * Version: {$package->version}\n";
	}
	if ( stripos( $line, ">define( 'WC_ADMIN_VERSION_NUMBER'," ) !== false ) {
		$line = "\t\t\$this->define( 'WC_ADMIN_VERSION_NUMBER', '{$package->version}' );\n";
	}
	$lines[] = $line;
}
file_put_contents( 'woocommerce-admin.php', $lines );
