<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\QueryComplexity;

/**
 * QueryComplexity validation rule that returns a generic error message when the complexity is exceeded.
 *
 * Admins can still read both values via debug mode; see
 * {@see GraphQLController} step 8.
 */
class QueryComplexityRule extends QueryComplexity {
	/**
	 * Override webonyx's default ("Max query complexity should be {max} but
	 * got {count}.").
	 *
	 * @param int $max   The configured maximum complexity (unused).
	 * @param int $count The computed query complexity (unused).
	 */
	public static function maxQueryComplexityErrorMessage( int $max, int $count ): string {
		return 'Maximum query complexity exceeded.';
	}
}
