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

	/**
	 * Sets the expectation for a "doing it wrong" being thrown.
	 *
	 * @param string $method_name The method name inside the error message.
	 */
	private function expect_doing_it_wrong_cogs_disabled( string $method_name ) {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_doing_it_wrong' => function ( $function_name, $message ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( "Doing it wrong, function: '$function_name', message: '$message'" );
				},
			)
		);

		$this->expectExceptionMessage( "Doing it wrong, function: '{$method_name}', message: 'The Cost of Goods sold feature is disabled, thus the method called will do nothing and will return dummy data.'" );
	}
}
