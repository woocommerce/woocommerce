<?php
/**
 * Handles polling and storage of specs
 */

namespace Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

/**
 * Specs data source poller class.
 * This handles polling specs from JSON endpoints.
 */
class DataSourcePoller {
	const DATA_SOURCES = array(
		'https://woocommerce.com/wp-json/wccom/obw-free-extensions/2.0/extensions.json',
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
		$data_sources = apply_filters( 'woocommerce_admin_remote_free_extensions_data_sources', self::DATA_SOURCES );

		// Note that this merges the specs from the data sources based on the
		// key - last one wins.
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
	 * @param Array  $specs_to_merge_in The specs to merge in to $specs.
	 * @param Array  $specs             The list of specs being merged into.
	 * @param string $url               The url of the feed being merged in (for error reporting).
	 */
	private static function merge_specs( $specs_to_merge_in, &$specs, $url ) {
		foreach ( $specs_to_merge_in as $spec ) {
			if ( ! self::validate_spec( $spec, $url ) ) {
				continue;
			}

			$key           = $spec->key;
			$specs[ $key ] = $spec;
		}
	}

	/**
	 * Validate the spec.
	 *
	 * @param object $spec The spec to validate.
	 * @param string $url  The url of the feed that provided the spec.
	 *
	 * @return bool The result of the validation.
	 */
	private static function validate_spec( $spec, $url ) {
		$logger         = self::get_logger();
		$logger_context = array( 'source' => $url );

		if ( ! isset( $spec->key ) ) {
			$logger->error(
				'Spec is invalid because the key is missing in feed',
				$logger_context
			);
			// phpcs:ignore
			$logger->error( print_r( $spec, true ), $logger_context );

			return false;
		}

		return true;
	}
}
