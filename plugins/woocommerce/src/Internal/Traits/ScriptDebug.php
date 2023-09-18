<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\Jetpack\Constants;

/**
 * Trait ScriptDebug
 *
 * @since x.x.x
 */
trait ScriptDebug {

	/**
	 * Get the script suffix based on the SCRIPT_DEBUG constant.
	 *
	 * @return string
	 */
	protected function get_script_suffix(): string {
		return Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
	}
}
