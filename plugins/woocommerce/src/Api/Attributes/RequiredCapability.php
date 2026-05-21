<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;
use Automattic\WooCommerce\Api\Infrastructure\Principal;

/**
 * Declares a WordPress capability required to execute a query or mutation.
 *
 * This attribute is repeatable: apply it multiple times to require several
 * capabilities (logical AND).
 *
 * Mutually exclusive with #[PublicAccess] on the same class.
 */
#[Attribute( Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS )]
final class RequiredCapability {
	/**
	 * Constructor.
	 *
	 * @param string $capability A WordPress capability slug
	 *                           (e.g. 'manage_woocommerce').
	 */
	public function __construct(
		public readonly string $capability,
	) {
	}

	/**
	 * Decide whether the given principal holds the required capability.
	 *
	 * Reads the WordPress user from the principal wrapper and delegates to
	 * {@see \user_can()}. Anonymous principals (the WP user has `ID === 0`)
	 * never hold any capability, so the check returns false naturally.
	 *
	 * @param Principal $principal The resolved request principal.
	 */
	public function authorize( Principal $principal ): bool {
		return user_can( $principal->user, $this->capability );
	}
}
