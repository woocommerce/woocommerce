<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Enums\Products;

use Automattic\WooCommerce\Api\Attributes\Deprecated;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;

#[Description( 'The publication status of a product.' )]
enum ProductStatus: string {
	#[Description( 'The product is a draft.' )]
	case Draft = 'draft';

	#[Description( 'The product is pending review.' )]
	case Pending = 'pending';

	#[Name( 'ACTIVE' )]
	#[Description( 'The product is published and visible.' )]
	case Published = 'publish';

	#[Description( 'The product is privately published.' )]
	case Private = 'private';

	#[Description( 'The product is scheduled to be published in the future.' )]
	case Future = 'future';

	#[Deprecated( 'Trashed products should be excluded via status filter.' )]
	#[Description( 'The product is in the trash.' )]
	case Trash = 'trash';

	#[Description( 'The product status is not one of the standard WordPress values (e.g. added by a plugin). Inspect raw_status for the underlying value.' )]
	case Other = 'other';
}
