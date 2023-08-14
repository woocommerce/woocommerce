<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal;

/**
 * Interface RegisterHooksInterface
 *
 * @since x.x.x
 */
interface RegisterHooksInterface {

	/**
	 * Register this class instance to the appropriate hooks.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register();
}
