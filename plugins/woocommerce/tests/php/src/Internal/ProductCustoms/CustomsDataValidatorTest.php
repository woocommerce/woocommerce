<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductCustoms;

use Automattic\WooCommerce\Internal\ProductCustoms\CustomsDataValidator;
use WC_Data_Exception;
use WC_Unit_Test_Case;

/**
 * Tests for customs field validation.
 */
class CustomsDataValidatorTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should normalize customs fields without losing leading zeroes.
	 * @testWith ["commodity_code", " 01.02-03 ", "010203"]
	 *           ["commodity_code", "00123456789012", "00123456789012"]
	 *           ["commodity_code", "01/02\t03", "010203"]
	 *           ["country_of_origin", " us ", "US"]
	 *           ["description", " <b>Cotton</b> shirt\n ", "Cotton shirt"]
	 *           ["description", "Chemise été", "Chemise été"]
	 *           ["description", "100%acrylic yarn", "100%acrylic yarn"]
	 *           ["description", "Salt & pepper", "Salt & pepper"]
	 *           ["description", "Men's T-shirt (cotton), 2/pk.", "Men's T-shirt (cotton), 2/pk."]
	 *           ["description", "Café Kaffee 咖啡", "Café Kaffee 咖啡"]
	 *           ["description", "Price $5 + tax, size = M", "Price $5 + tax, size = M"]
	 *           ["description", "<<b>b>Coffee", "Coffee"]
	 *           ["description", "<<<i>i>script>alert(1)", "alert(1)"]
	 * @param string      $field Field suffix.
	 * @param string|null $value Input value.
	 * @param string|null $expected Normalized value.
	 */
	public function test_normalizes_fields( string $field, $value, ?string $expected ): void {
		$this->assertSame( $expected, CustomsDataValidator::{"normalize_$field"}( $value ), 'Customs normalization should preserve the meaningful value.' );
	}

	/**
	 * @testdox Should treat null and whitespace-only fields as unset.
	 * @testWith [null]
	 *           [""]
	 *           [" \t\n"]
	 * @param string|null $value Empty value.
	 */
	public function test_normalizes_empty_values( $value ): void {
		foreach ( array( 'commodity_code', 'country_of_origin', 'description' ) as $field ) {
			$this->assertNull( CustomsDataValidator::{"normalize_$field"}( $value ), "An empty $field should be null." );
		}
	}

	/**
	 * @testdox Should reject malformed customs values with field-specific errors.
	 * @testWith ["commodity_code", "12345"]
	 *           ["commodity_code", "123456789012345"]
	 *           ["commodity_code", "HS 123456"]
	 *           ["commodity_code", "123456$"]
	 *           ["commodity_code", "１２３４５６"]
	 *           ["commodity_code", "---"]
	 *           ["commodity_code", []]
	 *           ["country_of_origin", "ZZ"]
	 *           ["country_of_origin", "USA"]
	 *           ["country_of_origin", []]
	 *           ["description", []]
	 *           ["description", "Cotton shirt 👕"]
	 *           ["description", "Mug™"]
	 *           ["description", "Size 1️⃣"]
	 *           ["description", "Price €5"]
	 * @param string $field Field suffix.
	 * @param mixed  $value Invalid value.
	 */
	public function test_rejects_invalid_values( string $field, $value ): void {
		try {
			CustomsDataValidator::{"normalize_$field"}( $value );
			$this->fail( 'Invalid customs data should throw a data exception.' );
		} catch ( WC_Data_Exception $exception ) {
			$this->assertSame( 'woocommerce_product_invalid_customs_' . $field, $exception->getErrorCode(), 'The error should identify the invalid field.' );
			$this->assertSame( 400, $exception->getCode(), 'Invalid customs data should be a client error.' );
		}
	}

	/**
	 * @testdox Should validate origins against all countries even when selling is restricted.
	 */
	public function test_country_is_not_limited_to_selling_countries(): void {
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', array( 'US' ) );

		$this->assertSame( 'RO', CustomsDataValidator::normalize_country_of_origin( 'ro' ), 'An origin country need not be a selling destination.' );
	}

	/**
	 * @testdox Should allow thirty-five Unicode code points after removing markup.
	 */
	public function test_description_limit_counts_unicode_code_points(): void {
		$value = str_repeat( 'é', 35 );

		$this->assertSame( $value, CustomsDataValidator::normalize_description( '<b>' . $value . '</b>' ), 'Multibyte characters should count once.' );
		$this->expectException( WC_Data_Exception::class );
		CustomsDataValidator::normalize_description( $value . 'é' );
	}

	/**
	 * @testdox Should reject descriptions that are not valid UTF-8.
	 */
	public function test_description_rejects_invalid_utf8(): void {
		$this->expectException( WC_Data_Exception::class );
		CustomsDataValidator::normalize_description( "Cotton \xC3\x28" );
	}

	/**
	 * @testdox Should lightly normalize stored values without validating them.
	 * @testWith ["customs_commodity_code", " 12 ", "12"]
	 *           ["customs_country_of_origin", " zz ", "ZZ"]
	 *           ["customs_description", " <b>Anything goes, even when it is longer than 35 characters</b> ", "<b>Anything goes, even when it is longer than 35 characters</b>"]
	 *           ["customs_description", " \t", null]
	 *           ["customs_commodity_code", 123456, null]
	 * @param string      $field Customs prop name.
	 * @param mixed       $value Stored value.
	 * @param string|null $expected Normalized value.
	 */
	public function test_normalize_stored_value( string $field, $value, ?string $expected ): void {
		$this->assertSame( $expected, CustomsDataValidator::normalize_stored_value( $field, $value ), 'Stored values should only be trimmed, uppercased for countries, and blank or non-string values cleared.' );
	}

	/**
	 * @testdox Should preserve explicit clears and omit fields outside the requested customs subset.
	 */
	public function test_normalizes_requested_subset(): void {
		$this->assertSame(
			array(
				'customs_commodity_code' => '010203',
				'customs_description'    => null,
			),
			CustomsDataValidator::normalize_fields(
				array(
					'customs_commodity_code' => '01.02.03',
					'customs_description'    => '',
					'name'                   => 'Shirt',
				)
			),
			'Only supplied customs fields should be returned, including explicit nulls.'
		);
	}

	/**
	 * @testdox Should reject a batch when a later customs field is invalid.
	 */
	public function test_validates_entire_subset(): void {
		$this->expectException( WC_Data_Exception::class );
		CustomsDataValidator::normalize_fields(
			array(
				'customs_commodity_code'    => '010203',
				'customs_country_of_origin' => 'ZZ',
			)
		);
	}
}
