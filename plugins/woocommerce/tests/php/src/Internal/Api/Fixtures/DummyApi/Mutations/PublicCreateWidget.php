<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Mutations;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\PublicAccess;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\InputTypes\CreateWidgetInput;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Store;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\Widget;

/**
 * Publicly-accessible widget creator used by input-level authorization tests.
 * Anyone can invoke the mutation (no class-level gate), but the
 * `CreateWidgetInput::$weight` property carries a `#[RequiredCapability]`, so
 * the input-side gate fires only when `weight` is provided in the request.
 */
#[Name( 'publicCreateWidget' )]
#[Description( 'Create a widget without query-level gating; input-side gates apply.' )]
#[PublicAccess]
class PublicCreateWidget {
	public function execute( CreateWidgetInput $input ): Widget {
		$widget = Store::create_widget( $input->label, $input->color );
		if ( null !== $input->weight ) {
			$widget->caption = sprintf( 'weighs %d g', $input->weight );
		}
		return $widget;
	}
}
