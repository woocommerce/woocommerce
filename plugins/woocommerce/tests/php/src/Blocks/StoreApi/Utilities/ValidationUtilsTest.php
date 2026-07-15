<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\ValidationUtils;
use WC_Unit_Test_Case;

/**
 * Tests for ValidationUtils.
 */
class ValidationUtilsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ValidationUtils
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ValidationUtils();
	}

	/**
	 * @testdox Legacy Nepal zone codes remain valid for persisted addresses.
	 */
	public function test_legacy_nepal_zone_codes_remain_valid_for_persisted_addresses(): void {
		$this->assertTrue( $this->sut->validate_state( 'BAG', 'NP' ), 'Persisted legacy Nepal zones should remain valid.' );
	}

	/**
	 * @testdox Legacy Nepal zone names normalize to their stored codes.
	 */
	public function test_legacy_nepal_zone_names_normalize_to_their_stored_codes(): void {
		$this->assertSame( 'BHE', $this->sut->format_state( 'Bheri', 'NP' ), 'Legacy Nepal zone names should normalize to their stored codes.' );
	}

	/**
	 * @testdox Current province names take precedence over duplicate legacy zone names.
	 */
	public function test_current_province_names_take_precedence_over_duplicate_legacy_zone_names(): void {
		$this->assertSame( 'P3', $this->sut->format_state( 'Bagmati', 'NP' ), 'Current province names should normalize to current codes.' );
	}
}
