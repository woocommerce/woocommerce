<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Queries;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Store;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\Widget;

#[Name( 'widget' )]
#[Description( 'Fetch a single widget by ID' )]
#[RequiredCapability( 'manage_options' )]
class GetWidget {
	public function execute(
		#[Description( 'The ID of the widget to fetch' )]
		int $id,
	): ?Widget {
		return Store::get_widget( $id );
	}
}
