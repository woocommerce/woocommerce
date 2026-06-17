<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryDepth;

/**
 * QueryDepth validation rule that returns a generic error message when the depth is exceeded.
 *
 * Admins can still read both values via debug mode; see
 * {@see GraphQLController} step 8.
 */
class QueryDepthRule extends QueryDepth {
	/**
	 * Override webonyx's default ("Max query depth should be {max} but
	 * got {count}.").
	 *
	 * @param int $max   The configured maximum depth (unused).
	 * @param int $count The computed query depth (unused).
	 */
	public static function maxQueryDepthErrorMessage( int $max, int $count ): string {
		return 'Maximum query depth exceeded.';
	}
}
