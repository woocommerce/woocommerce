<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Interfaces;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;

/**
 * Interface trait exposing a numeric identifier.
 *
 * Carries a class-level #[Name] override so the GraphQL interface name is
 * `HasId`. Pairs with the un-renamed {@see Named} trait so both branches of
 * the interface-name code path are covered.
 */
#[Name( 'HasId' )]
#[Description( 'An object with a numeric identifier' )]
trait Identifiable {
	#[Description( 'The unique numeric identifier' )]
	public int $id;
}
