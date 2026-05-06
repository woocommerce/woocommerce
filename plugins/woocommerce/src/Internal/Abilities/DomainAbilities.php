<?php
/**
 * Domain Abilities class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility wrapper for domain ability registration.
 */
class DomainAbilities {

	/**
	 * Initialize domain ability registration.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	final public static function init(): void {
		AbilitiesLoader::init();
	}

	/**
	 * Register canonical domain abilities.
	 *
	 * @since 10.9.0
	 */
	public static function register_abilities(): void {
		AbilitiesLoader::register_abilities();
	}
}
