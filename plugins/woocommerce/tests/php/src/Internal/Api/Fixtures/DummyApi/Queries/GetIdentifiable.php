<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\PublicAccess;
use Automattic\WooCommerce\Api\Attributes\ReturnType;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Interfaces\Named;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Store;

/**
 * Returns an interface type — exercises #[ReturnType] (since PHP cannot
 * type-hint a trait, the method returns `object`).
 *
 * The argument toggles the concrete type returned so tests can verify the
 * interface's `resolveType` callback selects the right ObjectType.
 */
#[Name( 'namedThing' )]
#[Description( 'Return either a Widget or a Gadget, both of which implement Named' )]
#[PublicAccess]
class GetIdentifiable {
	#[ReturnType( Named::class )]
	public function execute(
		#[Description( 'Which kind of object to return' )]
		string $kind,
	): object {
		if ( 'gadget' === $kind ) {
			return Store::build_gadget( 99, 'Sample Gadget', 7 );
		}
		Store::seed();
		return Store::get_widget( 1 );
	}
}
