<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Attributes;

use Attribute;

/**
 * Tells the builder to skip the annotated element entirely.
 *
 * Apply to a class to exclude it from API discovery (e.g. helper classes that
 * live in a scanned namespace but are not part of the API), or to a property
 * to omit it from the generated GraphQL type.
 */
#[Attribute]
final class Ignore {
}
