<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Parsers\DataKeywordTrait;
use Opis\JsonSchema\Parsers\KeywordParser;
use Opis\JsonSchema\Parsers\SchemaParser;

/**
 * Adds date format comparisons to Opis, which does not provide them itself.
 *
 * @internal
 */
final class DateFormatLimitParser extends KeywordParser {

	use DataKeywordTrait;

	/**
	 * Applies date comparisons only to string values.
	 *
	 * @since 11.2.0
	 *
	 * @return string The value type.
	 */
	public function type(): string {
		return self::TYPE_STRING;
	}

	/**
	 * Parses a literal date limit or a reference to another date.
	 *
	 * @since 11.2.0
	 *
	 * @param SchemaInfo   $info   The schema to parse.
	 * @param SchemaParser $parser The Opis schema parser.
	 * @param object       $shared State shared by the keyword parsers.
	 * @return Keyword|null The date comparison, if present.
	 * @throws \Opis\JsonSchema\Exceptions\InvalidKeywordException If the format or limit is unsupported.
	 */
	public function parse( SchemaInfo $info, SchemaParser $parser, object $shared ): ?Keyword {
		unset( $shared );
		if ( ! $this->keywordExists( $info ) ) {
			return null;
		}

		if ( 'date' !== ( $info->data()->format ?? null ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Opis requires the schema object, which is not output.
			throw $this->keywordException( '{keyword} requires format: date.', $info );
		}

		$value = $this->keywordValue( $info );
		if ( $this->isDataKeywordAllowed( $parser, $this->keyword ) ) {
			$pointer = $this->getDataKeywordPointer( $value );
			if ( $pointer ) {
				return new DateFormatLimit( $this->keyword, $pointer );
			}
		}

		if ( ! is_string( $value ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Opis requires the schema object, which is not output.
			throw $this->keywordException( '{keyword} must contain a string or a valid $data reference.', $info );
		}

		return new DateFormatLimit( $this->keyword, $value );
	}
}
