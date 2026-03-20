<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Marks a query or mutation as publicly accessible without authentication.
 *
 * When present, the generated resolver skips all capability checks, allowing
 * any user (including unauthenticated visitors) to execute the operation.
 *
 * Mutually exclusive with #[RequiredCapability] on the same class.
 */
#[Attribute( Attribute::TARGET_CLASS )]
final class PublicAccess {
}
