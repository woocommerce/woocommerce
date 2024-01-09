<?php

namespace Automattic\WooCommerce\Admin\Features\MarketingRecommendations;

defined( 'ABSPATH' ) || exit;

/**
 * Marketing Recommendations engine.
 * This goes through the specs and gets marketing recommendations.
 */
class Init {
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
	 * Load recommended plugins from Woo.com
	 *
	 * @return array
	 */
	public static function get_recommended_plugins(): array {
		$specs  = self::get_specs();
		$result = array();

		foreach ( $specs as $spec ) {
			$result[] = self::object_to_array( $spec );
		}

		return $result;
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
	 * @return array
	 */
	protected static function object_to_array( $obj ) {
		if ( is_object( $obj ) ) {
			$obj = (array) $obj;
		}
		if ( is_array( $obj ) ) {
			$new = array();
			foreach ( $obj as $key => $val ) {
				$new[ $key ] = self::object_to_array( $val );
			}
		} else {
			$new = $obj;
		}
		return $new;
	}
}
