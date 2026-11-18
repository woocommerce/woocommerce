<?php
/**
 * POC bootstrap: loads WooPayments after WooCommerce, then the normal test bootstrap.
 *
 * Run with: WC_POC_WOOPAYMENTS_DIR=/path/to/woocommerce-payments vendor/bin/phpunit -c phpunit.xml \
 *   --bootstrap tests/php/src/Internal/AbilitiesApi/WooPaymentsPoc/bootstrap.php --group woopayments-poc
 */

$wcpay_dir = getenv( 'WC_POC_WOOPAYMENTS_DIR' );
if ( $wcpay_dir ) {
	$GLOBALS['wp_filter']['muplugins_loaded'][20][] = array(
		'function'      => static function () use ( $wcpay_dir ) {
			define( 'WCPAY_TEST_ENV', true );
			update_option( 'woocommerce_currency', 'USD' );
			require $wcpay_dir . '/woocommerce-payments.php';
		},
		'accepted_args' => 1,
	);
}

require __DIR__ . '/../../../../../legacy/bootstrap.php';
