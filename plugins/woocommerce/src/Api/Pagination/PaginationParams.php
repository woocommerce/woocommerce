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
	 * Maximum number of items a client may request in a single page.
	 *
	 * Requests with `first` or `last` above this value are rejected with an
	 * INVALID_ARGUMENT error, matching the behavior of common GraphQL APIs
	 * (e.g. GitHub's 100-item cap).
	 */
	public const MAX_PAGE_SIZE = 100;

	/**
	 * Page size used when neither `first` nor `last` is provided.
	 */
	public const DEFAULT_PAGE_SIZE = 100;

	/**
	 * Constructor.
	 *
	 * @param ?int    $first  Return the first N results.
	 * @param ?int    $last   Return the last N results.
	 * @param ?string $after  Return results after this cursor.
	 * @param ?string $before Return results before this cursor.
	 *
	 * @throws \InvalidArgumentException When `first` or `last` is negative or exceeds MAX_PAGE_SIZE.
	 */
	public function __construct(
		#[Description( 'Return the first N results. Must be between 0 and ' . self::MAX_PAGE_SIZE . '.' )]
		public readonly ?int $first = null,
		#[Description( 'Return the last N results. Must be between 0 and ' . self::MAX_PAGE_SIZE . '.' )]
		public readonly ?int $last = null,
		#[Description( 'Return results after this cursor.' )]
		public readonly ?string $after = null,
		#[Description( 'Return results before this cursor.' )]
		public readonly ?string $before = null,
	) {
		self::validate_limit( 'first', $first );
		self::validate_limit( 'last', $last );
	}

	/**
	 * The page size to use when no explicit `first` or `last` is provided.
	 *
	 * Exposed as a method (not just the constant) so the default can become
	 * configurable — e.g. via a filter or store option — without requiring
	 * call-site changes.
	 *
	 * @return int
	 */
	public static function get_default_page_size(): int {
		return self::DEFAULT_PAGE_SIZE;
	}

	/**
	 * Validate pagination limits on a raw args array without constructing a
	 * full PaginationParams instance.
	 *
	 * Intended for call sites that take raw GraphQL args (like nested
	 * connection resolvers) and forward them to Connection::slice(). The
	 * constructor already runs the same checks for root queries that build
	 * a PaginationParams via #[Unroll], so this keeps both paths in sync.
	 *
	 * @param array $args Raw args with optional `first` / `last` keys.
	 *
	 * @throws \InvalidArgumentException When either limit is negative or above MAX_PAGE_SIZE.
	 */
	public static function validate_args( array $args ): void {
		self::validate_limit( 'first', $args['first'] ?? null );
		self::validate_limit( 'last', $args['last'] ?? null );
	}

	/**
	 * Validate a `first` / `last` argument.
	 *
	 * @param string $name  The argument name, for the error message.
	 * @param ?int   $value The value to validate.
	 *
	 * @throws \InvalidArgumentException When the value is out of range.
	 */
	private static function validate_limit( string $name, ?int $value ): void {
		if ( null === $value ) {
			return;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML output; serialized as JSON in the GraphQL error response.
		if ( $value < 0 ) {
			throw new \InvalidArgumentException(
				sprintf( 'Argument `%s` must be zero or greater.', $name )
			);
		}

		if ( $value > self::MAX_PAGE_SIZE ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Argument `%s` exceeds the maximum page size of %d.',
					$name,
					self::MAX_PAGE_SIZE
				)
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}
}
