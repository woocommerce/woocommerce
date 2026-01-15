<?php
/**
 * WooCommerce Coupons Functions
 *
 * Functions for coupon specific things.
 *
 * @package WooCommerce\Functions
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore as CouponsDataStore;

/**
 * Get coupon types.
 *
 * @return array
 */
function wc_get_coupon_types() {
	return (array) apply_filters(
		'woocommerce_coupon_discount_types',
		array(
			'percent'       => __( 'Percentage discount', 'woocommerce' ),
			'fixed_cart'    => __( 'Fixed cart discount', 'woocommerce' ),
			'fixed_product' => __( 'Fixed product discount', 'woocommerce' ),
		)
	);
}

/**
 * Get a coupon type's name.
 *
 * @param string $type Coupon type.
 * @return string
 */
function wc_get_coupon_type( $type = '' ) {
	$types = wc_get_coupon_types();
	return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

/**
 * Coupon types that apply to individual products. Controls which validation rules will apply.
 *
 * @since  2.5.0
 * @return array
 */
function wc_get_product_coupon_types() {
	return (array) apply_filters( 'woocommerce_product_coupon_types', array( 'fixed_product', 'percent' ) );
}

/**
 * Coupon types that apply to the cart as a whole. Controls which validation rules will apply.
 *
 * @since  2.5.0
 * @return array
 */
function wc_get_cart_coupon_types() {
	return (array) apply_filters( 'woocommerce_cart_coupon_types', array( 'fixed_cart' ) );
}

/**
 * Check if coupons are enabled.
 * Filterable.
 *
 * @since  2.5.0
 *
 * @return bool
 */
function wc_coupons_enabled() {
	return apply_filters( 'woocommerce_coupons_enabled', 'yes' === get_option( 'woocommerce_enable_coupons' ) );
}

/**
 * Check if two coupon codes are the same.
 * Lowercasing to ensure case-insensitive comparison.
 *
 * @since 9.9.0
 *
 * @param string $coupon_1 Coupon code 1.
 * @param string $coupon_2 Coupon code 2.
 * @return bool
 */
function wc_is_same_coupon( $coupon_1, $coupon_2 ) {
	return wc_strtolower( $coupon_1 ) === wc_strtolower( $coupon_2 );
}

/**
 * Get coupon code by ID.
 *
 * @since 3.0.0
 * @param int $id Coupon ID.
 * @return string
 */
function wc_get_coupon_code_by_id( $id ) {
	$data_store = WC_Data_Store::load( 'coupon' );
	return empty( $id ) ? '' : (string) $data_store->get_code_by_id( $id );
}

/**
 * Get coupon ID by code.
 *
 * @since 3.0.0
 * @param string $code    Coupon code.
 * @param int    $exclude Used to exclude an ID from the check if you're checking existence.
 * @return int
 */
function wc_get_coupon_id_by_code( $code, $exclude = 0 ) {

	if ( StringUtil::is_null_or_whitespace( $code ) ) {
		return 0;
	}

	$data_store = WC_Data_Store::load( 'coupon' );
	// Coupon code allows spaces, which doesn't work well with some cache engines (e.g. memcached).
	$hashed_code = md5( wc_strtolower( $code ) );
	$cache_key   = WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $hashed_code;

	$ids = wp_cache_get( $cache_key, 'coupons' );

	if ( false === $ids ) {
		$ids = $data_store->get_ids_by_code( $code );
		if ( $ids ) {
			wp_cache_set( $cache_key, $ids, 'coupons' );
		}
	}

	$ids = array_diff( array_filter( array_map( 'absint', (array) $ids ) ), array( $exclude ) );

	return apply_filters( 'woocommerce_get_coupon_id_from_code', absint( current( $ids ) ), $code, $exclude );
}

/**
 * Repair coupon lookup entries with zero discount_amount. A bug in WC 9.9 (fixed in 10.0)
 * caused discount_amount to be set to zero when a coupon code was used with
 * different case (e.g. "10-off" vs "10-OFF").
 *
 * @since 10.1.0
 * @return array Array with 'success' boolean and 'message' string.
 */
function wc_repair_zero_discount_coupons_lookup_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'wc_order_coupon_lookup';

	// Check if table exists.
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
		return array(
			'success' => false,
			'message' => __( 'Coupons lookup table does not exist.', 'woocommerce' ),
		);
	}

	// Get entries with zero discount_amount.
	$zero_discount_entries = $wpdb->get_results(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT order_id, coupon_id FROM $table_name WHERE discount_amount = %f",
			0.0
		),
		ARRAY_A
	);

	if ( empty( $zero_discount_entries ) ) {
		return array(
			'success' => true,
			'message' => __( 'No entries with zero discount amount found. Coupons lookup table is up to date.', 'woocommerce' ),
		);
	}

	$processed_count = 0;
	$error_count     = 0;

	foreach ( $zero_discount_entries as $entry ) {
		try {
			$result = CouponsDataStore::sync_order_coupons( $entry['order_id'] );
			if ( false !== $result ) {
				++$processed_count;
			} else {
				++$error_count;
			}
		} catch ( Exception $e ) {
			++$error_count;
			$logger = wc_get_logger();
			$logger->error(
				sprintf(
					'Error fixing coupon lookup entry for order %d: %s',
					$entry['order_id'],
					$e->getMessage()
				),
				array(
					'source'   => 'coupons-lookup-fix',
					'order_id' => $entry['order_id'],
					'error'    => $e,
				)
			);
		}
	}

	// Clear any related caches.
	wp_cache_flush_group( 'coupons' );
	WC_Cache_Helper::get_transient_version( 'woocommerce_reports', true );

	$message = sprintf(
		/* translators: %1$d: number of entries processed, %2$d: number of errors */
		__( 'Coupons lookup table entries with zero discount amount repaired successfully. Processed %1$d entries with %2$d errors.', 'woocommerce' ),
		$processed_count,
		$error_count
	);

	return array(
		'success' => true,
		'message' => $message,
	);
}
