<?php
/**
 * Init/register importers for WooCommerce.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Importers
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

register_importer( 'woocommerce_tax_rate_csv', __( 'WooCommerce Tax Rates (CSV)', 'woocommerce' ), __( 'Import <strong>tax rates</strong> to your store via a csv file.', 'woocommerce'), 'woocommerce_tax_rates_importer' );

/**
 * woocommerce_tax_rates_importer function.
 *
 * @access public
 * @return void
 */
function woocommerce_tax_rates_importer() {

	// Load Importer API
	require_once ABSPATH . 'wp-admin/includes/import.php';

	if ( ! class_exists( 'WP_Importer' ) ) {
		$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
		if ( file_exists( $class_wp_importer ) )
			require $class_wp_importer;
	}

	// includes
	require dirname( __FILE__ ) . '/tax-rates-importer.php';

	// Dispatch
	$WC_CSV_Tax_Rates_Import = new WC_CSV_Tax_Rates_Import();

	$WC_CSV_Tax_Rates_Import->dispatch();
}