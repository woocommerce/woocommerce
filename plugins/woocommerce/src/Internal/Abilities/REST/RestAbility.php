<?php
/**
 * REST Ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Custom WP_Ability subclass for REST API-based abilities.
 *
 * This class extends the base WP_Ability class but skips output validation
 * to handle the discrepancies between WooCommerce REST API schemas and
 * actual output. This is necessary because WooCommerce schemas are often
 * incomplete or inaccurate regarding nullable fields and type variations.
 */
class RestAbility extends \WP_Ability {

	/**
	 * Skip output validation for REST abilities.
	 *
	 * WooCommerce REST API schemas often don't accurately reflect the actual
	 * output, particularly for nullable fields and type variations. Rather than
	 * trying to fix all schema inconsistencies, we skip output validation for
	 * REST-based abilities while maintaining input validation and permissions.
	 *
	 * @param mixed $output The output to validate.
	 * @return true Always returns true (no validation).
	 */
	protected function validate_output( $output ) {
		// Skip validation - trust that REST controllers return valid data.
		return true;
	}
}
