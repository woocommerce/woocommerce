<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\JsonPointer;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Keywords\ErrorTrait;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;

/**
 * Compares date strings using the ajv-formats limit keywords.
 *
 * @internal
 */
final class DateFormatLimit implements Keyword {

	use ErrorTrait;

	/**
	 * The format comparison keyword.
	 *
	 * @var string
	 */
	private $keyword;

	/**
	 * The date limit or a reference to its value.
	 *
	 * @var string|JsonPointer
	 */
	private $limit;

	/**
	 * Sets the comparison and its limit.
	 *
	 * @since 11.2.0
	 *
	 * @param string             $keyword The comparison keyword.
	 * @param string|JsonPointer $limit   The date limit or data reference.
	 */
	public function __construct( string $keyword, $limit ) {
		$this->keyword = $keyword;
		$this->limit   = $limit;
	}

	/**
	 * Checks the date against its limit without changing either value.
	 *
	 * @since 11.2.0
	 *
	 * @param ValidationContext $context The current document and value.
	 * @param Schema            $schema  The schema being evaluated.
	 * @return ValidationError|null The comparison error, if any.
	 */
	public function validate( ValidationContext $context, Schema $schema ): ?ValidationError {
		$limit = $this->limit instanceof JsonPointer
			? $this->limit->data( $context->rootData(), $context->currentDataPath(), $this )
			: $this->limit;

		if ( ! is_string( $limit ) ) {
			return $this->error( $schema, $context, $this->keyword, 'The date limit must be a string.' );
		}

		$comparison = strcmp( $context->currentData(), $limit );
		switch ( $this->keyword ) {
			case 'formatMinimum':
				$valid = $comparison >= 0;
				break;
			case 'formatMaximum':
				$valid = $comparison <= 0;
				break;
			case 'formatExclusiveMinimum':
				$valid = $comparison > 0;
				break;
			default:
				$valid = $comparison < 0;
		}

		return $valid ? null : $this->error(
			$schema,
			$context,
			$this->keyword,
			'Date does not satisfy {keyword}: {limit}.',
			array(
				'keyword' => $this->keyword,
				'limit'   => $limit,
			)
		);
	}
}
