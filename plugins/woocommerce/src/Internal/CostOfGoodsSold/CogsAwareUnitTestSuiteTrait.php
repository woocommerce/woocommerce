<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CostOfGoodsSold;

/**
 * Trait with common functionality for unit tests related to the Cost of Goods Sold feature.
 */
trait CogsAwareUnitTestSuiteTrait {
	/**
	 * Enable the Cost of Goods Sold feature.
	 */
	private function enable_cogs_feature() {
		update_option( 'woocommerce_feature_cost_of_goods_sold_enabled', 'yes' );
	}

	/**
	 * Enable the Cost of Goods Sold feature.
	 */
	private function disable_cogs_feature() {
		delete_option( 'woocommerce_feature_cost_of_goods_sold_enabled' );
	}
}
