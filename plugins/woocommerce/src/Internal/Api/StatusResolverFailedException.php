<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

/**
 * Internal sentinel raised by {@see GraphQLController::pick_status()} when a
 * plugin-supplied HTTP status resolver throws.
 *
 * The resolver is documented as "must not throw"; this exception lets the
 * controller distinguish a resolver bug from any other Throwable so it can
 * short-circuit to a fixed-shape 500 response without re-invoking the
 * resolver. Never surfaced on the wire.
 *
 * @internal
 */
final class StatusResolverFailedException extends \RuntimeException {
}
