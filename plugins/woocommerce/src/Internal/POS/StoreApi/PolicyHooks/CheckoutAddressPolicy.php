<?php
/**
 * CheckoutAddressPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Opts POS requests out of the Store API's address validation pipeline.
 *
 * The Store API enforces per-field address rules (postcode, phone, etc.), but
 * the common in-store sale is a cash purchase of physical goods where the
 * cashier has no customer address to capture. Disabling the
 * `woocommerce_store_api_validate_addresses` filter (added in
 * {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::validate_addresses})
 * lets POS send empty addresses. A cart-aware per-product-type requirements
 * model (downloadables, gift cards, shipped goods) is a planned follow-up.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class CheckoutAddressPolicy implements RegisterHooksInterface {

	/**
	 * Register hooks. No-op on non-POS requests.
	 */
	public function register(): void {
		if ( ! Context::is_pos_request() ) {
			return;
		}
		add_filter( 'woocommerce_store_api_validate_addresses', '__return_false' );
	}
}
