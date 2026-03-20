<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Pagination;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Unroll;

/**
 * Standard pagination parameters for connection queries.
 *
 * Because this class carries #[Unroll], whenever it is used as an execute()
 * parameter the builder expands its properties into individual GraphQL arguments.
 */
#[Unroll]
class PaginationParams {
	/**
	 * Constructor.
	 *
	 * @param ?int    $first  Return the first N results.
	 * @param ?int    $last   Return the last N results.
	 * @param ?string $after  Return results after this cursor.
	 * @param ?string $before Return results before this cursor.
	 */
	public function __construct(
		#[Description( 'Return the first N results.' )]
		public readonly ?int $first = null,
		#[Description( 'Return the last N results.' )]
		public readonly ?int $last = null,
		#[Description( 'Return results after this cursor.' )]
		public readonly ?string $after = null,
		#[Description( 'Return results before this cursor.' )]
		public readonly ?string $before = null,
	) {
	}
}
