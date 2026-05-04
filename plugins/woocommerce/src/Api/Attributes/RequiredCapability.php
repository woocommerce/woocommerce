<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Declares a WordPress capability required to execute a query or mutation.
 *
 * The generated resolver checks `current_user_can()` for every declared
 * capability before invoking the command. If any check fails, an
 * UNAUTHORIZED error is returned. This attribute is repeatable: apply it
 * multiple times to require several capabilities.
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
}
