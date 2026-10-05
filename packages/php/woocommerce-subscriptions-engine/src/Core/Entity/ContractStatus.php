<?php
/**
 * ContractStatus - the engine's default contract status slugs plus read helpers
 * over the {@see StatusRegistry}.
 *
 * Contract status is opaque engine data. The constants name the engine defaults:
 * the defaults are shared slugs and carry no engine meaning; the engine enforces
 * no transitions. Extensions may register more through
 * {@see StatusRegistry::register()}. The
 * {@see Contract} entity refuses to write a status that is not registered.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Entity
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Entity;

defined( 'ABSPATH' ) || exit;

/**
 * ContractStatus value/helper class.
 */
final class ContractStatus {

	public const ACTIVE               = 'active';
	public const ON_HOLD              = 'on-hold';
	public const PENDING_CANCELLATION = 'pending-cancellation';
	public const CANCELLED            = 'cancelled';
	public const EXPIRED              = 'expired';

	/**
	 * The engine's default contract statuses (the registry seed).
	 *
	 * @return array<int, string>
	 */
	public static function get_defaults(): array {
		return array(
			self::ACTIVE,
			self::ON_HOLD,
			self::PENDING_CANCELLATION,
			self::CANCELLED,
			self::EXPIRED,
		);
	}

	/**
	 * Every registered contract status: the engine defaults, then extension
	 * registrations.
	 *
	 * @return array<int, string>
	 */
	public static function get_all(): array {
		return StatusRegistry::get_all( StatusRegistry::KIND_CONTRACT );
	}

	/**
	 * Whether `$status` is a registered contract status (an engine default or an extension
	 * registration). Write paths accept only registered statuses.
	 *
	 * @param string $status Status to check.
	 */
	public static function is_registered( string $status ): bool {
		return StatusRegistry::is_registered( StatusRegistry::KIND_CONTRACT, $status );
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
