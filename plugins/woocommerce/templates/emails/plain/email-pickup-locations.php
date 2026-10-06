<?php
/**
 * Email pickup locations (plain text)
 *
 * Shows the pickup locations the customer chose at checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-pickup-locations.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.3.0
 *
 * @var array<int, array{name: string, address: string, details: string}> $pickup_locations Pickup locations.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $pickup_locations ) ) {
	return;
}

echo esc_html( wc_strtoupper( _n( 'Pickup location', 'Pickup locations', count( $pickup_locations ), 'woocommerce' ) ) ) . "\n\n";

foreach ( $pickup_locations as $pickup_location ) {
	echo esc_html( wp_strip_all_tags( $pickup_location['name'] ) ) . "\n";

	if ( '' !== $pickup_location['address'] ) {
		echo esc_html( wp_strip_all_tags( $pickup_location['address'] ) ) . "\n";
	}

	if ( '' !== $pickup_location['details'] ) {
		echo esc_html( wp_strip_all_tags( $pickup_location['details'] ) ) . "\n";
	}

	echo "\n";
}
