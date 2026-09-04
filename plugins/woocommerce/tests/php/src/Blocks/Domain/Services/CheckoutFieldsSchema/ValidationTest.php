<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services\CheckoutFieldsSchema;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\Validation;
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
}
