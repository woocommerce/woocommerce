<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CostOfGoodsSold;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Trait with general Cost of Goods Sold related functionality shared by the entire codebase.
 */
trait CogsAwareTrait {

	/**
	 * Check if the Cost of Goods Sold feature is enabled.
	 *
	 * @param string|null $doing_it_wrong_function_name If not null, a "doing it wrong" error will be thrown with this function name if the deature is disabled.
	 *
	 * @return bool True if the feature is enabled.
	 */
	protected function cogs_is_enabled( ?string $doing_it_wrong_function_name = null ): bool {
		if ( wc_get_container()->get( CostOfGoodsSoldController::class )->feature_is_enabled() ) {
			return true;
		}

		if ( $doing_it_wrong_function_name ) {
			wc_get_container()->get( LegacyProxy::class )->call_function(
				'wc_doing_it_wrong',
				$doing_it_wrong_function_name,
				'The Cost of Goods sold feature is disabled, thus the method called will do nothing and will return dummy data.',
				'9.5.0'
			);
		}

		return false;
	}
}
