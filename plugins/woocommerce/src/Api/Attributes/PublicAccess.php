<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marks a query or mutation as publicly accessible without authentication.
 *
 * Mutually exclusive with #[RequiredCapability] (and any other authorization
 * attribute) on the same class — this is a hard build error.
 *
 * Placement on a property (output field or input field) is accepted but is
 * a build warning and a runtime no-op: it always grants, which is
 * indistinguishable from the default allow-by-default semantics for fields
 * that carry no authorization attribute.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY )]
final class PublicAccess {
	/**
	 * Always grants access.
	 */
	public function authorize(): bool {
		return true;
	}
}
