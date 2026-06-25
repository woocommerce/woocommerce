<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Attributes;

use Attribute;
use Automattic\WooCommerce\Api\Infrastructure\Principal;

/**
 * Fixture authorization attribute that exercises the opt-in `$_metadata`
 * slot. Grants iff the surrounding command class carries an `#[Internal]`
 * metadata entry (i.e. `$_metadata['query']['internal'] === true`).
 *
 * Declaring both the principal and `$_metadata` covers the
 * happy-path mixed-positional/named call shape ApiBuilder and
 * {@see \Automattic\WooCommerce\Api\Infrastructure\ResolverHelpers::compute_preauthorized()}
 * must produce.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY )]
final class RequiresInternalFlag {
	public function authorize( Principal $principal, array $_metadata ): bool {
		unset( $principal );
		return true === ( $_metadata['query']['internal'] ?? null );
	}
}
