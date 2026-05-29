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
 * The Store API enforces per-field address rules (postcode, phone, etc.)
 * via OrderController::validate_addresses. For web checkout this prevents
 * customers accidentally submitting incomplete information; for POS it
 * blocks the most common in-store retail flow (cash sale of physical goods
 * where the cashier has no customer address to capture). The POS route
 * also relaxes the schema-level required flag on billing/shipping address;
 * this hook handles the deeper pipeline layer so mobile can truly send
 * empty addresses.
 *
 * Relies on the `woocommerce_store_api_validate_addresses` filter — added
 * in {@see \Automattic\WooCommerce\StoreApi\Utilities\OrderController::validate_addresses}.
 *
 * For product types that genuinely need an address (downloadables sold
 * by email, gift cards, shipped goods sold for delivery), the cashier
 * captures the relevant fields and the standard validation would still
 * be appropriate — a smarter cart-aware per-product-type requirements
 * model is a planned follow-up.
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
