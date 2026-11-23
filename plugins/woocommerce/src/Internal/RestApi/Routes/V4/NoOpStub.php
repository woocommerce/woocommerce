<?php
/**
 * No-op REST API V4 controller (compatibility stub).
 *
 * Kept for compatibility with the upgrade path to 11.5.0, aliased from each removed v4
 * controller's old class name. Registers nothing.
 *
 * @deprecated 11.5.0
 * @package Automattic\WooCommerce\Internal\RestApi\Routes\V4
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4;

defined( 'ABSPATH' ) || exit;

/**
 * No-op controller.
 */
class NoOpStub {

	/**
	 * Register routes. Intentionally a no-op.
	 */
	public function register_routes(): void {}
}
