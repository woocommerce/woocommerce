<?php
/**
 * Ability definition interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Defines a WooCommerce ability registration class.
 *
 * @since 10.9.0
 */
interface AbilityDefinition {

	/**
	 * Get the ability name.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public static function get_name(): string;

	/**
	 * Get the arguments used to register the ability with the WordPress Abilities API.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array;
}
