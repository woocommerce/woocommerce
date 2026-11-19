<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RegulatoryFields\Formats;

use Automattic\WooCommerce\Internal\RegulatoryFields\Formats\BrazilTaxId;
use WC_Unit_Test_Case;

/**
 * Tests for the BrazilTaxId class.
 */
class BrazilTaxIdTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BrazilTaxId
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new BrazilTaxId();
	}

	/**
	 * @testdox Should remove punctuation and spaces and uppercase letters.
	 *
	 * @testWith ["529.982.247-25", "52998224725"]
	 *           [" 529 982 247 25 ", "52998224725"]
	 *           ["11.222.333/0001-81", "11222333000181"]
	 *           ["12.abc.345/01de-35", "12ABC34501DE35"]
	 *
	 * @param string $typed    Value as typed.
	 * @param string $expected Normalized value.
	 */
	public function test_normalize( string $typed, string $expected ): void {
		$this->assertSame( $expected, $this->sut->normalize( $typed ) );
	}

	/**
	 * @testdox Should accept a CPF, a numeric CNPJ and an alphanumeric CNPJ with correct check digits.
	 *
	 * @testWith ["52998224725"]
	 *           ["11144477735"]
	 *           ["11222333000181"]
	 *           ["12ABC34501DE35"]
	 *
	 * @param string $value Normalized value.
	 */
	public function test_is_valid_accepts_real_numbers( string $value ): void {
		$this->assertTrue( $this->sut->is_valid( $value ), "$value should be accepted" );
	}

	/**
	 * @testdox Should reject wrong check digits, repeated digits, wrong lengths and letters where only digits are allowed.
	 *
	 * @testWith ["52998224726"]
	 *           ["11222333000180"]
	 *           ["12ABC34501DE36"]
	 *           ["11111111111"]
	 *           ["00000000000000"]
	 *           ["1234"]
	 *           ["5299822472A"]
	 *           ["12ABC34501DE3A"]
	 *           [""]
	 *
	 * @param string $value Normalized value.
	 */
	public function test_is_valid_rejects_bad_numbers( string $value ): void {
		$this->assertFalse( $this->sut->is_valid( $value ), "'$value' should be rejected" );
	}

	/**
	 * @testdox Should add CPF or CNPJ punctuation by length and leave other values unchanged.
	 *
	 * @testWith ["52998224725", "529.982.247-25"]
	 *           ["12ABC34501DE35", "12.ABC.345/01DE-35"]
	 *           ["1234", "1234"]
	 *
	 * @param string $value    Normalized value.
	 * @param string $expected Formatted value.
	 */
	public function test_format( string $value, string $expected ): void {
		$this->assertSame( $expected, $this->sut->format( $value ) );
	}
}
