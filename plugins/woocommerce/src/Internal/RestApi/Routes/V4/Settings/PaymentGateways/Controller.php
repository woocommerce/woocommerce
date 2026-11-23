<?php
/**
 * REST API V4 Payment Gateways Settings controller (compatibility stub).
 *
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways;

defined( 'ABSPATH' ) || exit;

/**
 * Payment Gateways Settings controller.
 *
 * The real controller was removed when the experimental `rest-api-v4` namespace was removed. This
 * empty stub stays so a pre-swap in-memory controller list, still naming this class while the files
 * are swapped during an update, can instantiate it instead of fataling on the deleted file. It
 * registers nothing.
 *
 * @deprecated 11.4.0
 */
class Controller {

	/**
	 * Register routes. Intentionally a no-op.
	 */
	public function register_routes(): void {}
}
