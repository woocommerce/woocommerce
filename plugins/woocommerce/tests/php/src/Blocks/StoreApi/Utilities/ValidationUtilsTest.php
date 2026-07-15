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
	 * @testdox Every legacy Nepal zone remains valid and readable for persisted addresses.
	 *
	 * @dataProvider provide_legacy_nepal_states
	 *
	 * @param string $code               Legacy state code.
	 * @param string $name               Legacy state name.
	 * @param string $expected_name_code Expected code when formatting the name.
	 */
	public function test_legacy_nepal_zone_codes_remain_valid_for_persisted_addresses( string $code, string $name, string $expected_name_code ): void {
		$this->assertTrue( $this->sut->validate_state( $code, 'NP' ), "Persisted legacy Nepal zone {$code} should remain valid." );
		$this->assertSame( $code, $this->sut->format_state( $code, 'NP' ), "Persisted legacy Nepal zone {$code} should remain unchanged." );
		$this->assertSame( $expected_name_code, $this->sut->format_state( $name, 'NP' ), "Legacy Nepal zone name {$name} should normalize predictably." );
	}

	/**
	 * @testdox Current province names take precedence over duplicate legacy zone names.
	 */
	public function test_current_province_names_take_precedence_over_duplicate_legacy_zone_names(): void {
		$this->assertSame( 'P3', $this->sut->format_state( 'Bagmati', 'NP' ), 'Current province names should normalize to current codes.' );
	}

	/**
	 * @testdox Free-form state behavior is preserved when an extension removes Nepal's state list.
	 */
	public function test_empty_filtered_state_list_remains_free_form(): void {
		$original_countries = WC()->countries;
		$filter_callback    = static function ( array $states ): array {
			$states['NP'] = array();

			return $states;
		};

		add_filter( 'woocommerce_states', $filter_callback );
		WC()->countries = new \WC_Countries();

		try {
			$this->assertTrue( $this->sut->validate_state( 'CUSTOM', 'NP' ), 'An empty state list should continue to accept free-form input.' );
			$this->assertSame( 'Bheri', $this->sut->format_state( 'Bheri', 'NP' ), 'Legacy aliases should not recreate a fixed state list.' );
		} finally {
			WC()->countries = $original_countries;
			remove_filter( 'woocommerce_states', $filter_callback );
		}
	}

	/**
	 * @testdox Extensions can disable legacy state-code compatibility.
	 */
	public function test_legacy_compatibility_filter_can_disable_aliases(): void {
		$filter_callback = '__return_empty_array';
		add_filter( 'woocommerce_legacy_state_codes', $filter_callback );

		try {
			$this->assertFalse( $this->sut->validate_state( 'BAG', 'NP' ), 'Disabled legacy aliases should no longer pass validation.' );
		} finally {
			remove_filter( 'woocommerce_legacy_state_codes', $filter_callback );
		}
	}

	/**
	 * Provide every legacy Nepal state and its expected name normalization.
	 *
	 * Current province names intentionally win when they duplicate legacy zone names.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public static function provide_legacy_nepal_states(): array {
		return array(
			'Bagmati'    => array( 'BAG', 'Bagmati', 'P3' ),
			'Bheri'      => array( 'BHE', 'Bheri', 'BHE' ),
			'Dhaulagiri' => array( 'DHA', 'Dhaulagiri', 'DHA' ),
			'Gandaki'    => array( 'GAN', 'Gandaki', 'P4' ),
			'Janakpur'   => array( 'JAN', 'Janakpur', 'JAN' ),
			'Karnali'    => array( 'KAR', 'Karnali', 'P6' ),
			'Koshi'      => array( 'KOS', 'Koshi', 'P1' ),
			'Lumbini'    => array( 'LUM', 'Lumbini', 'P5' ),
			'Mahakali'   => array( 'MAH', 'Mahakali', 'MAH' ),
			'Mechi'      => array( 'MEC', 'Mechi', 'MEC' ),
			'Narayani'   => array( 'NAR', 'Narayani', 'NAR' ),
			'Rapti'      => array( 'RAP', 'Rapti', 'RAP' ),
			'Sagarmatha' => array( 'SAG', 'Sagarmatha', 'SAG' ),
			'Seti'       => array( 'SET', 'Seti', 'SET' ),
		);
	}
}
