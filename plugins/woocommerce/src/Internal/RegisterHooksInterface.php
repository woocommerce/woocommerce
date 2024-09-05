<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal;

/**
 * Interface RegisterHooksInterface
 *
 * @since 8.5.0
 */
interface RegisterHooksInterface {

	/**
	 * Register this class instance to the appropriate hooks.
	 *
	 * @return void
	 */
	public function register();
}
