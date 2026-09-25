<?php
/**
 * Aggregate product customs data for the WooCommerce tracker.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductCustoms;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Counts published products and variations with customs values in the periodic tracker snapshot.
 */
class Telemetry implements RegisterHooksInterface {

	/**
	 * Snapshot keys by "post type/meta key".
	 */
	private const COUNT_KEYS = array(
		'product/_customs_commodity_code'              => 'products_with_commodity_code',
		'product/_customs_country_of_origin'           => 'products_with_country_of_origin',
		'product/_customs_description'                 => 'products_with_customs_description',
		'product_variation/_customs_commodity_code'    => 'variations_with_commodity_code',
		'product_variation/_customs_country_of_origin' => 'variations_with_country_of_origin',
	);

	/**
	 * Registers the periodic snapshot filter.
	 *
	 * @since 11.3.0
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_tracker_data', array( $this, 'handle_woocommerce_tracker_data' ) );
	}

	/**
	 * Handle the woocommerce_tracker_data filter by adding aggregate customs counts.
	 *
	 * @internal
	 *
	 * @param mixed $data The tracker payload, which third-party filters may have changed.
	 * @return mixed
	 */
	public function handle_woocommerce_tracker_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$data['product_customs'] = $this->collect_snapshot();
		return $data;
	}

	/**
	 * Counts published products and variations with stored customs values.
	 *
	 * @return array<string, int>
	 */
	private function collect_snapshot(): array {
		global $wpdb;

		$counts = array_fill_keys( self::COUNT_KEYS, 0 );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_type, pm.meta_key, COUNT(DISTINCT pm.post_id) AS product_count
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key IN (%s, %s, %s)
				   AND pm.meta_value <> ''
				   AND p.post_type IN (%s, %s)
				   AND p.post_status = %s
				 GROUP BY p.post_type, pm.meta_key",
				'_customs_commodity_code',
				'_customs_country_of_origin',
				'_customs_description',
				'product',
				'product_variation',
				ProductStatus::PUBLISH
			)
		);

		foreach ( (array) $rows as $row ) {
			$key = self::COUNT_KEYS[ $row->post_type . '/' . $row->meta_key ] ?? null;
			if ( null !== $key ) {
				$counts[ $key ] = (int) $row->product_count;
			}
		}

		return $counts;
	}
}
