<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\InputTypes;

use Automattic\WooCommerce\Api\Attributes\ArrayOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\ScalarType;
use Automattic\WooCommerce\Api\InputTypes\TracksProvidedFields;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums\Color;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Scalars\DummyDateTime;

/**
 * Input type for creating a widget.
 */
#[Description( 'Data needed to create a new widget' )]
class CreateWidgetInput {
	use TracksProvidedFields;

	#[Description( 'The widget label' )]
	public string $label;

	#[Description( 'Optional weight in grams' )]
	#[RequiredCapability( 'manage_woocommerce' )]
	public ?int $weight = null;

	#[Description( 'The widget color' )]
	public Color $color;

	#[Description( 'Tag IDs to attach to the widget' )]
	#[ArrayOf( 'int' )]
	public ?array $tag_ids = null;

	#[Description( 'When the widget should expire' )]
	#[ScalarType( DummyDateTime::class )]
	public ?string $expires_at = null;
}
