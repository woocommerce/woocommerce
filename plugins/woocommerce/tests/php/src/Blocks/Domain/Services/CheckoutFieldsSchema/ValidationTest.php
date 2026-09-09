<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services\CheckoutFieldsSchema;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\Validation;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use WP_Error;
use WP_UnitTestCase;

/**
 * Tests for the Validation class.
 */
class ValidationTest extends WP_UnitTestCase {

	/**
	 * @testdox A $data reference is accepted wherever the keyword's own value would be.
	 *
	 * One keyword per shape draft-07 gives these keywords: a number, a string, and an array.
	 *
	 * @testWith ["exclusiveMinimum"]
	 *           ["pattern"]
	 *           ["required"]
	 *
	 * @param string $keyword The keyword to set a $data reference on.
	 */
	public function test_data_references_are_accepted( string $keyword ) {
		$rules = array( $keyword => array( '$data' => '1/plugin~1other-field' ) );

		$this->assertTrue( Validation::is_valid_schema( $rules ), sprintf( 'The "%s" keyword should accept a $data reference.', $keyword ) );
	}

	/**
	 * @testdox A keyword that cannot take a $data reference still rejects one.
	 */
	public function test_data_reference_on_unsupported_keyword_is_rejected() {
		$this->assertInstanceOf( WP_Error::class, Validation::is_valid_schema( array( 'type' => array( '$data' => '1/plugin~1other-field' ) ) ) );
	}

	/**
	 * @testdox A malformed $data reference is rejected.
	 *
	 * @testWith [{"$data": 1}]
	 *           [{"$data": "1/plugin~1other-field", "extra": true}]
	 *           [{"$data": "not-a-pointer"}]
	 *           [{"$data": "/bad~2escape"}]
	 *
	 * @param array $reference The malformed reference.
	 */
	public function test_malformed_data_references_are_rejected( array $reference ) {
		$this->assertInstanceOf( WP_Error::class, Validation::is_valid_schema( array( 'exclusiveMinimum' => $reference ) ) );
	}

	/**
	 * @testdox Literal keyword values are still validated against their own types.
	 *
	 * @testWith [{"exclusiveMinimum": 20260101}, true]
	 *           [{"exclusiveMinimum": "20260101"}, false]
	 *           [{"type": "nonsense"}, false]
	 *           [{"required": "a"}, false]
	 *
	 * @param array $rules      The rules to validate.
	 * @param bool  $is_allowed Whether the rules should be accepted.
	 */
	public function test_literal_values_keep_their_constraints( array $rules, bool $is_allowed ) {
		$this->assertSame( $is_allowed, ! is_wp_error( Validation::is_valid_schema( $rules ) ), 'Widening a keyword for $data should not let a wrongly typed literal through.' );
	}
	/**
	 * @testdox Date limits compare strings with literal and cross-field bounds.
	 *
	 * @testWith ["formatMinimum", "2026-05-01", false]
	 *           ["formatMinimum", "2026-05-02", true]
	 *           ["formatMinimum", "2026-05-03", true]
	 *           ["formatMaximum", "2026-05-01", true]
	 *           ["formatMaximum", "2026-05-02", true]
	 *           ["formatMaximum", "2026-05-03", false]
	 *           ["formatExclusiveMinimum", "2026-05-01", false]
	 *           ["formatExclusiveMinimum", "2026-05-02", false]
	 *           ["formatExclusiveMinimum", "2026-05-03", true]
	 *           ["formatExclusiveMaximum", "2026-05-01", true]
	 *           ["formatExclusiveMaximum", "2026-05-02", false]
	 *           ["formatExclusiveMaximum", "2026-05-03", false]
	 *           ["formatMaximum", "2026-02-30", false]
	 *           ["formatMaximum", "2026--02--01", false]
	 *           ["formatMaximum", "2026/02/01", false]
	 *
	 * @param string $keyword  The comparison keyword.
	 * @param string $value    The date being validated.
	 * @param bool   $expected Whether the date should pass.
	 */
	public function test_date_format_limits( string $keyword, string $value, bool $expected ): void {
		$sut = $this->createMock( DocumentObject::class );
		$sut->method( 'get_data' )->willReturn(
			array(
				'date'            => $value,
				'hotel/reference' => '2026-05-02',
			)
		);

		foreach ( array( '2026-05-02', array( '$data' => '1/hotel~1reference' ), array( '$data' => '/hotel~1reference' ) ) as $limit ) {
			$rules = array(
				'properties' => array(
					'date' => array(
						'type'   => 'string',
						'format' => 'date',
						$keyword => $limit,
					),
				),
			);

			$this->assertTrue( Validation::is_valid_schema( $rules ) );
			$this->assertSame( $expected, ! is_wp_error( Validation::validate_document_object( $sut, $rules ) ) );
		}
	}

	/**
	 * @testdox Date limit rules require a date format and a date string or valid pointer.
	 *
	 * @testWith [{"formatMinimum": "2026-05-01"}, false]
	 *           [{"format": "time", "formatMinimum": "12:00:00Z"}, false]
	 *           [{"format": "date", "formatMaximum": 20260501}, false]
	 *           [{"format": "date", "formatMaximum": "2026-02-30"}, false]
	 *           [{"format": "date", "formatMinimum": {"$data": "not-a-pointer"}}, false]
	 *           [{"format": "date", "formatMinimum": "2026-05-01"}, true]
	 *           [{"format": "date", "formatMaximum": {"$data": "1/reference"}}, true]
	 *
	 * @param array $rules    The field rules.
	 * @param bool  $expected Whether registration should accept them.
	 */
	public function test_date_limit_schema_registration( array $rules, bool $expected ): void {
		$this->assertSame( $expected, ! is_wp_error( Validation::is_valid_schema( $rules ) ) );
	}
}
