<?php
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Admin\Features\MarketingRecommendations;

defined( 'ABSPATH' ) || exit;

/**
 * Default Marketing Recommendations
 */
class DefaultMarketingRecommendations {
	/**
	 * Get default specs.
	 *
	 * @return array Default specs.
	 */
	public static function get_all() {
		// Read the JSON file.
		$json = file_get_contents( __DIR__ . '/recommendations.json', true );

		// Decode the JSON file.
		$json_data = json_decode( $json, true );

		return $json_data;
	}
}
