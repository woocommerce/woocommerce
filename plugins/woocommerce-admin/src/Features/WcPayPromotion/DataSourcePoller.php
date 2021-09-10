<?php
/**
 * Handles polling and storage of specs
 */

namespace Automattic\WooCommerce\Admin\Features\WcPayPromotion;

defined( 'ABSPATH' ) || exit;

/**
 * Specs data source poller class.
 * This handles polling specs from JSON endpoints.
 */
class DataSourcePoller {
	/**
	 * Name of data sources filter.
	 */
	const FILTER_NAME = 'woocommerce_admin_payment_method_promotions_data_sources';

	/**
	 * Default data sources array.
	 */
	const DATA_SOURCES = array(
		'https://woocommerce.com/wp-json/wccom/payment-gateway-suggestions/1.0/payment-method/promotions.json',
	);

	/**
	 * The logger instance.
	 *
	 * @var WC_Logger|null
	 */
	protected static $logger = null;

	/**
	 * Get the logger instance.
	 *
	 * @return WC_Logger
	 */
	private static function get_logger() {
		if ( is_null( self::$logger ) ) {
			self::$logger = wc_get_logger();
		}

		return self::$logger;
	}

	/**
	 * Reads the data sources for specs and persists those specs.
	 *
	 * @return bool Whether any specs were read.
	 */
	public static function read_specs_from_data_sources() {
		$specs        = array();
		$data_sources = apply_filters( self::FILTER_NAME, self::DATA_SOURCES );

		// Note that this merges the specs from the data sources based on the
		// product - last one wins.
		foreach ( $data_sources as $url ) {
			$specs_from_data_source = self::read_data_source( $url );
			self::merge_specs( $specs_from_data_source, $specs, $url );
		}

		return $specs;
	}

	/**
	 * Read a single data source and return the read specs
	 *
	 * @param string $url The URL to read the specs from.
	 *
	 * @return array The specs that have been read from the data source.
	 */
	private static function read_data_source( $url ) {
		$logger_context = array( 'source' => $url );
		$logger         = self::get_logger();
		$response       = wp_remote_get(
			add_query_arg(
				'_locale',
				get_user_locale(),
				$url
			)
		);

		if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
			$logger->error(
				'Error getting remote payment method data feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $response, true ), $logger_context );

			return [];
		}

		$body  = $response['body'];
		$specs = json_decode( $body );

		if ( null === $specs ) {
			$logger->error(
				'Empty response in remote payment method data feed',
				$logger_context
			);

			return [];
		}

		if ( ! is_array( $specs ) ) {
			$logger->error(
				'Remote payment method data feed is not an array',
				$logger_context
			);

			return [];
		}

		return $specs;
	}

	/**
	 * Merge the specs.
	 *
	 * @param Array $specs_to_merge_in The specs to merge in to $specs.
	 * @param Array $specs             The list of specs being merged into.
	 */
	private static function merge_specs( $specs_to_merge_in, &$specs ) {
		foreach ( $specs_to_merge_in as $spec ) {
			$id           = $spec->id;
			$specs[ $id ] = $spec;
		}
	}
}
