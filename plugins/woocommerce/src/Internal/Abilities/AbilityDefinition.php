<?php
/**
 * Ability definition interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Defines a WooCommerce ability registration class.
 */
interface AbilityDefinition {

	/**
	 * Register the ability with the WordPress Abilities API.
	 *
	 * @since 10.9.0
	 */
	public static function register(): void;
}
