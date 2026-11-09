<?php
/**
 * PlanStatus - the engine's default plan status slugs plus read helpers over
 * the {@see StatusRegistry}.
 *
 * Plan status is opaque engine data. The defaults are shared slugs and carry no
 * engine meaning; the engine enforces no transitions. Extensions may register
 * more through {@see StatusRegistry::register()}. The {@see Plan} entity refuses
 * to write a status that is not registered.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

defined( 'ABSPATH' ) || exit;

/**
 * PlanStatus value/helper class.
 */
final class PlanStatus {

	public const ACTIVE   = 'active';
	public const ARCHIVED = 'archived';

	/**
	 * The engine's default plan statuses (the registry seed).
	 *
	 * @return array<int, string>
	 */
	public static function get_defaults(): array {
		return array(
			self::ACTIVE,
			self::ARCHIVED,
		);
	}

	/**
	 * Every registered plan status: the engine defaults, then extension
	 * registrations.
	 *
	 * @return array<int, string>
	 */
	public static function get_all(): array {
		return StatusRegistry::get_all( StatusRegistry::KIND_PLAN );
	}

	/**
	 * Whether `$status` is a registered plan status (an engine default or an
	 * extension registration). Write paths accept only registered statuses.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_registered( string $status ): bool {
		return StatusRegistry::is_registered( StatusRegistry::KIND_PLAN, $status );
	}

	/**
	 * Whether `$status` is a well-formed status slug (lowercase letters and digits in
	 * words joined by single hyphens, at most 20 characters), registered or not.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_valid( string $status ): bool {
		return StatusRegistry::is_valid_slug( $status );
	}
}
