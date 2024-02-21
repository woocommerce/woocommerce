<?php

namespace Automattic\WooCommerce\Admin\Features\MarketingRecommendations;

use Automattic\WooCommerce\Admin\RemoteSpecs\RemoteSpecsEngine;

defined( 'ABSPATH' ) || exit;

/**
 * Marketing Recommendations engine.
 * This goes through the specs and gets marketing recommendations.
 */
class Init extends RemoteSpecsEngine {
	/**
	 * Slug of the category specifying marketing extensions on the Woo.com store.
	 *
	 * @var string
	 */
	const MARKETING_EXTENSION_CATEGORY_SLUG = 'marketing';

	/**
	 * Slug of the subcategory specifying marketing channels on the Woo.com store.
	 *
	 * @var string
	 */
	const MARKETING_CHANNEL_SUBCATEGORY_SLUG = 'sales-channels';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_updated', array( __CLASS__, 'delete_specs_transient' ) );
	}

	/**
	 * Delete the specs transient.
	 */
	public static function delete_specs_transient() {
		MarketingRecommendationsDataSourcePoller::get_instance()->delete_specs_transient();
	}

	/**
	 * Get specs or fetch remotely if they don't exist.
	 */
	public static function get_specs() {
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return DefaultMarketingRecommendations::get_all();
		}
		$specs = MarketingRecommendationsDataSourcePoller::get_instance()->get_specs_from_data_sources();

		// Fetch specs if they don't yet exist.
		if ( ! is_array( $specs ) || 0 === count( $specs ) ) {
			return DefaultMarketingRecommendations::get_all();
		}

		return $specs;
	}

	/**
	 * Process specs.
	 *
	 * @param array|null $specs Marketing recommendations spec array.
	 * @return array
	 */
	protected static function evaluate_specs( array $specs = null ) {
		$suggestions = array();
		$errors      = array();

		foreach ( $specs as $spec ) {
			try {
				$suggestions[] = self::object_to_array( $spec );
			} catch ( \Throwable $e ) {
				$errors[] = $e;
			}
		}

		return array(
			'suggestions' => $suggestions,
			'errors'      => $errors,
		);
	}

	/**
	 * Load recommended plugins from Woo.com
	 *
	 * @return array
	 */
	public static function get_recommended_plugins(): array {
		$specs   = self::get_specs();
		$results = self::evaluate_specs( $specs );

		$specs_to_return = $results['suggestions'];
		$specs_to_save   = null;

		if ( empty( $specs_to_return ) ) {
			// When suggestions is empty, replace it with defaults and save for 3 hours.
			$specs_to_save   = DefaultMarketingRecommendations::get_all();
			$specs_to_return = self::evaluate_specs( $specs_to_save )['suggestions'];
		} elseif ( count( $results['errors'] ) > 0 ) {
			// When suggestions is not empty but has errors, save it for 3 hours.
			$specs_to_save = $specs;
		}

		if ( $specs_to_save ) {
			MarketingRecommendationsDataSourcePoller::get_instance()->set_specs_transient( $specs_to_save, 3 * HOUR_IN_SECONDS );
		}
		$errors = $results['errors'];
		if ( ! empty( $errors ) ) {
			self::log_errors( $errors );
		}

		return $specs_to_return;
	}

	/**
	 * Return only the recommended marketing channels from Woo.com.
	 *
	 * @return array
	 */
	public static function get_recommended_marketing_channels(): array {
		return array_filter(
			self::get_recommended_plugins(),
			function ( array $plugin_data ) {
				return self::is_marketing_channel_plugin( $plugin_data );
			}
		);
	}

	/**
	 * Return all recommended marketing extensions EXCEPT the marketing channels from Woo.com.
	 *
	 * @return array
	 */
	public static function get_recommended_marketing_extensions_excluding_channels(): array {
		return array_filter(
			self::get_recommended_plugins(),
			function ( array $plugin_data ) {
				return self::is_marketing_plugin( $plugin_data ) && ! self::is_marketing_channel_plugin( $plugin_data );
			}
		);
	}

	/**
	 * Returns whether a plugin is a marketing extension.
	 *
	 * @param array $plugin_data The plugin properties returned by the API.
	 *
	 * @return bool
	 */
	protected static function is_marketing_plugin( array $plugin_data ): bool {
		$categories = $plugin_data['categories'] ?? array();

		return in_array( self::MARKETING_EXTENSION_CATEGORY_SLUG, $categories, true );
	}

	/**
	 * Returns whether a plugin is a marketing channel.
	 *
	 * @param array $plugin_data The plugin properties returned by the API.
	 *
	 * @return bool
	 */
	protected static function is_marketing_channel_plugin( array $plugin_data ): bool {
		if ( ! self::is_marketing_plugin( $plugin_data ) ) {
			return false;
		}

		$subcategories = $plugin_data['subcategories'] ?? array();
		foreach ( $subcategories as $subcategory ) {
			if ( isset( $subcategory['slug'] ) && self::MARKETING_CHANNEL_SUBCATEGORY_SLUG === $subcategory['slug'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert an object to an array.
	 * This is used to convert the specs to an array so that they can be returned by the API.
	 *
	 * @param mixed $obj Object to convert.
	 * @param array &$visited Reference to an array keeping track of all seen objects to detect circular references.
	 * @return array
	 */
	public static function object_to_array( $obj, &$visited = array() ) {
		if ( is_object( $obj ) ) {
			if ( in_array( $obj, $visited, true ) ) {
				// Circular reference detected.
				return null;
			}
			$visited[] = $obj;
			$obj       = (array) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = self::object_to_array( $val, $visited );
			}
		} else {
			$new = $obj;
		}
		return $new;
	}
}
