<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Mutations;

use Automattic\WooCommerce\Api\Attributes\ArrayOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\InputTypes\CreateWidgetInput;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Store;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types\Widget;

/**
 * Creates a widget — exercises:
 * - input type to PHP class conversion in the generated resolver.
 * - object return type.
 * - #[RequiredCapability] enforcement.
 */
#[Description( 'Create a new widget' )]
#[RequiredCapability( 'manage_options' )]
class CreateWidget {
	public function execute(
		#[Description( 'The data for the new widget' )]
		CreateWidgetInput $input,
		#[Description( 'Related widget inputs for array input generation coverage' )]
		#[ArrayOf( CreateWidgetInput::class )]
		?array $related_inputs = null,
	): Widget {
		$widget = Store::create_widget( $input->label, $input->color );
		if ( null !== $input->weight ) {
			$widget->caption = sprintf( 'weighs %d g', $input->weight );
		}
		if ( $input->was_provided( 'tag_ids' ) && null !== $input->tag_ids ) {
			$widget->tag_ids = $input->tag_ids;
		}
		return $widget;
	}
}
