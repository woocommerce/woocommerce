<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\StoreApi;

defined( 'ABSPATH' ) || exit;

/**
 * POSSessionHandler class
 *
 * A session handler for Point of Sale (POS) requests. This extends the standard
 * Store API SessionHandler but can be identified as a POS session, allowing
 * the checkout flow to apply different validation rules (e.g., skipping
 * address requirements) for trusted POS clients.
 */
final class POSSessionHandler extends SessionHandler {

	/**
	 * Check if this is a POS session.
	 *
	 * @return bool Always returns true for POS sessions.
	 */
	public function is_pos_session(): bool {
		return true;
	}
}
