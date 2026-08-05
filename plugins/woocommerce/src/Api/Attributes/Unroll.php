<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Expands a class's properties into individual flat GraphQL arguments.
 *
 * When applied to a class, any `execute()` parameter of that type is
 * automatically unrolled. When applied to a specific `execute()` parameter,
 * only that usage is unrolled.
 *
 * Each public property of the target class becomes a separate GraphQL argument.
 * Properties marked with #[Ignore] are skipped, and #[Description] on
 * properties is forwarded to the generated argument descriptions.
 *
 * The generated resolver constructs the original class via its constructor,
 * passing the individual argument values as named parameters.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER )]
final class Unroll {
}
