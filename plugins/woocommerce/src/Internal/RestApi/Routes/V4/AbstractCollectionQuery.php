<?php
/**
 * AbstractCollectionQuery class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WC_Order;

/**
 * AbstractCollectionQuery class.
 *
 * @internal This class is for internal use only and should not be used by external code.
 */
abstract class AbstractCollectionQuery {
	/**
	 * Operator constants for easy access.
	 */
	const OPERATOR_IS                    = 'is';
	const OPERATOR_IS_NOT                = 'isNot';
	const OPERATOR_LESS_THAN             = 'lessThan';
	const OPERATOR_GREATER_THAN          = 'greaterThan';
	const OPERATOR_LESS_THAN_OR_EQUAL    = 'lessThanOrEqual';
	const OPERATOR_GREATER_THAN_OR_EQUAL = 'greaterThanOrEqual';
	const OPERATOR_BETWEEN               = 'between';

	/**
	 * Array of operators for validation.
	 */
	const OPERATORS = array(
		self::OPERATOR_IS,
		self::OPERATOR_IS_NOT,
		self::OPERATOR_LESS_THAN,
		self::OPERATOR_GREATER_THAN,
		self::OPERATOR_LESS_THAN_OR_EQUAL,
		self::OPERATOR_GREATER_THAN_OR_EQUAL,
		self::OPERATOR_BETWEEN,
	);

	/**
	 * Get query schema for collection.
	 *
	 * @return array
	 */
	abstract public function get_query_schema(): array;

	/**
	 * Prepares query args.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return array
	 */
	abstract public function get_query_args( WP_REST_Request $request ): array;

	/**
	 * Get results of the query.
	 *
	 * @param array           $query_args The query arguments.
	 * @param WP_REST_Request $request The request object.
	 * @return array
	 */
	abstract public function get_query_results( array $query_args, WP_REST_Request $request ): array;

	/**
	 * Convert a REST date that names its own UTC offset to the UTC 'Y-m-d H:i:s' form that GMT date columns store.
	 *
	 * WP_Date_Query re-expresses offset dates in the site timezone even when the column is GMT, shifting them by the site offset.
	 * Dates without an offset and values that can't be parsed are returned unchanged.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $date The date, usually an RFC 3339 string from a REST request.
	 * @return mixed The equivalent UTC date string, or the original value.
	 */
	protected static function to_gmt_date_query_value( $date ) {
		// Same offset check as rest_get_date_with_gmt(), so values without one stay byte-identical.
		if ( ! is_string( $date ) || ! preg_match( '#(Z|[+-]\d{2}(:\d{2})?)$#', $date ) ) {
			return $date;
		}

		$dates = rest_get_date_with_gmt( $date, true );

		return null === $dates ? $date : $dates[1];
	}
}
