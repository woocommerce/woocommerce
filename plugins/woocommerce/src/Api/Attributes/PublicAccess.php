<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marks a query or mutation as publicly accessible without authentication.
 *
 * Mutually exclusive with #[RequiredCapability] (and any other authorization
 * attribute) on the same class.
 */
#[Attribute( Attribute::TARGET_CLASS )]
final class PublicAccess {
	/**
	 * Always grants access.
	 */
	public function authorize(): bool {
		return true;
	}
}
