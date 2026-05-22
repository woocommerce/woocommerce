<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Types;

use Automattic\WooCommerce\Api\Attributes\ArrayOf;
use Automattic\WooCommerce\Api\Attributes\ConnectionOf;
use Automattic\WooCommerce\Api\Attributes\Deprecated;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\HiddenFromMetadataQuery;
use Automattic\WooCommerce\Api\Attributes\Ignore;
use Automattic\WooCommerce\Api\Attributes\Parameter;
use Automattic\WooCommerce\Api\Attributes\ParameterDescription;
use Automattic\WooCommerce\Api\Attributes\PublicAccess;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\ScalarType;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums\Color;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Enums\Priority;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Interfaces\Named;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Metadata\VisibleSampleMetadata;
use Automattic\WooCommerce\Tests\Internal\Api\Fixtures\DummyApi\Scalars\DummyDateTime;

/**
 * A widget — exercises every attribute applicable to an output type.
 */
#[Description( 'A dummy widget that exercises every output-type attribute' )]
class Widget {
	use Named;

	#[Description( 'A short slug' )]
	public string $slug;

	#[Description( 'An optional caption' )]
	#[VisibleSampleMetadata]
	#[RequiredCapability( 'manage_woocommerce' )]
	public ?string $caption;

	#[Description( 'The widget color' )]
	public Color $color;

	#[Description( 'Priority assigned to this widget' )]
	public Priority $priority;

	#[Description( 'Tag IDs assigned to this widget' )]
	#[ArrayOf( 'int' )]
	#[PublicAccess]
	public array $tag_ids;

	#[Description( 'Notable comments left on this widget' )]
	#[ArrayOf( WidgetReview::class )]
	public array $featured_reviews;

	#[Description( 'Reviews of the widget' )]
	#[ConnectionOf( WidgetReview::class )]
	public Connection $reviews;

	#[Description( 'When the widget was created' )]
	#[ScalarType( DummyDateTime::class )]
	public ?string $date_created;

	/**
	 * Demonstrates a forwarded #[Parameter] argument on a property.
	 *
	 * The matching #[ParameterDescription] is split out below to exercise
	 * that attribute's "augment without redeclaring the type" path.
	 */
	#[Description( 'The widget price' )]
	#[Parameter( name: 'formatted', type: 'bool', default: false )]
	#[ParameterDescription( name: 'formatted', description: 'When true, prepend a $ sign' )]
	public string $price;

	#[Description( 'A field flagged for removal' )]
	#[Deprecated( 'Use price instead.' )]
	#[HiddenFromMetadataQuery]
	public string $legacy_price;

	#[Ignore]
	public ?string $internal_notes;
}
