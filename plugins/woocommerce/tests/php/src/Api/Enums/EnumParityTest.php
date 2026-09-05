<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Enums;

use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus as ApiCouponStatus;
use Automattic\WooCommerce\Api\Enums\Coupons\DiscountType as ApiDiscountType;
use Automattic\WooCommerce\Api\Enums\Products\ProductStatus as ApiProductStatus;
use Automattic\WooCommerce\Api\Enums\Products\ProductType as ApiProductType;
use Automattic\WooCommerce\Api\Enums\Products\StockStatus as ApiStockStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductType;
use ReflectionClass;
use WC_Unit_Test_Case;

/**
 * Guards the two enum systems in the plugin against silent drift.
 *
 * `src/Enums/` holds `final` classes of string constants usable from PHP 7.4 code, and
 * `src/Api/Enums/` holds native backed enums for the PHP 8.1+ Code API. Several concepts
 * are declared in both, so a value added to one and forgotten in the other means the Code
 * API and the rest of the plugin disagree about a string that is persisted in the database.
 *
 * Each API enum below declares, case by case, the `src/Enums/` value it represents (or
 * `null` when it has no equivalent, such as the `Other` fallbacks). Adding or removing a
 * case on either side fails these tests until the map is updated deliberately.
 */
class EnumParityTest extends WC_Unit_Test_Case {

	/**
	 * Every enum under `src/Api/Enums/`, mapped to its `src/Enums/` counterpart.
	 *
	 * Keys are API enum class names. Each entry holds:
	 *  - `legacy`: the counterpart class in `src/Enums/`, or `null` when the concept only
	 *    exists in the Code API.
	 *  - `cases`: every case name of the API enum mapped to the `src/Enums/` value it
	 *    represents, or `null` for a case with no counterpart.
	 *  - `legacy_only`: counterpart values the API deliberately does not expose.
	 *
	 * @var array<class-string, array{legacy: class-string|null, cases: array<string, string|null>, legacy_only: array<int, string>}>
	 */
	private const API_ENUMS = array(
		ApiProductStatus::class => array(
			'legacy'      => ProductStatus::class,
			'cases'       => array(
				'Draft'     => ProductStatus::DRAFT,
				'Pending'   => ProductStatus::PENDING,
				'Published' => ProductStatus::PUBLISH,
				'Private'   => ProductStatus::PRIVATE,
				'Future'    => ProductStatus::FUTURE,
				'Trash'     => ProductStatus::TRASH,
				'Other'     => null,
			),
			// A product is never created through the Code API in the intermediate
			// auto-draft state WordPress assigns before a first save.
			'legacy_only' => array( ProductStatus::AUTO_DRAFT ),
		),
		ApiProductType::class   => array(
			'legacy'      => ProductType::class,
			'cases'       => array(
				'Simple'    => ProductType::SIMPLE,
				'Grouped'   => ProductType::GROUPED,
				'External'  => ProductType::EXTERNAL,
				'Variable'  => ProductType::VARIABLE,
				'Variation' => ProductType::VARIATION,
				'Other'     => null,
			),
			'legacy_only' => array(),
		),
		ApiStockStatus::class   => array(
			'legacy'      => ProductStockStatus::class,
			'cases'       => array(
				'InStock'     => ProductStockStatus::IN_STOCK,
				'OutOfStock'  => ProductStockStatus::OUT_OF_STOCK,
				'OnBackorder' => ProductStockStatus::ON_BACKORDER,
				'Other'       => null,
			),
			// `lowstock` is a reporting threshold rather than a stored stock status.
			'legacy_only' => array( ProductStockStatus::LOW_STOCK ),
		),
		ApiCouponStatus::class  => array(
			'legacy'      => null,
			'cases'       => array(
				'Published' => null,
				'Draft'     => null,
				'Pending'   => null,
				'Private'   => null,
				'Future'    => null,
				'Trash'     => null,
				'Other'     => null,
			),
			'legacy_only' => array(),
		),
		ApiDiscountType::class  => array(
			'legacy'      => null,
			'cases'       => array(
				'Percent'      => null,
				'FixedCart'    => null,
				'FixedProduct' => null,
				'Other'        => null,
			),
			'legacy_only' => array(),
		),
	);

	/**
	 * Provides one entry per mapped API enum.
	 *
	 * @return array<string, array{0: class-string}>
	 */
	public function api_enum_provider(): array {
		$data = array();
		foreach ( array_keys( self::API_ENUMS ) as $api_enum ) {
			$data[ $api_enum ] = array( $api_enum );
		}
		return $data;
	}

	/**
	 * @testdox Every enum file under src/Api/Enums is accounted for by this test.
	 */
	public function test_all_api_enums_are_mapped(): void {
		$declared = array();
		foreach ( array_keys( self::API_ENUMS ) as $api_enum ) {
			$declared[] = ( new ReflectionClass( $api_enum ) )->getShortName();
		}

		$found = array();
		foreach ( glob( WC_ABSPATH . 'src/Api/Enums/*/*.php' ) ?: array() as $file ) {
			$found[] = basename( $file, '.php' );
		}

		sort( $declared );
		sort( $found );

		$this->assertSame(
			$declared,
			$found,
			'A new enum under src/Api/Enums must be added to EnumParityTest::API_ENUMS, declaring whether it has a src/Enums counterpart.'
		);
	}

	/**
	 * @testdox An API enum declares exactly the cases this test maps.
	 *
	 * @dataProvider api_enum_provider
	 *
	 * @param class-string $api_enum The API enum under test.
	 */
	public function test_api_enum_cases_match_the_map( string $api_enum ): void {
		$expected = array_keys( self::API_ENUMS[ $api_enum ]['cases'] );
		$actual   = array_column( $api_enum::cases(), 'name' );

		sort( $expected );
		sort( $actual );

		$this->assertSame(
			$expected,
			$actual,
			"Cases of {$api_enum} changed. Update EnumParityTest::API_ENUMS, and check whether its src/Enums counterpart needs the same change."
		);
	}

	/**
	 * @testdox A string-backed API case carries the same value as its src/Enums counterpart.
	 *
	 * @dataProvider api_enum_provider
	 *
	 * @param class-string $api_enum The API enum under test.
	 */
	public function test_string_backed_case_values_match_the_counterpart( string $api_enum ): void {
		$cases = self::API_ENUMS[ $api_enum ]['cases'];

		$checked = 0;
		foreach ( $api_enum::cases() as $case ) {
			$legacy_value = $cases[ $case->name ];

			if ( null === $legacy_value || ! is_string( $case->value ) ) {
				continue;
			}

			$this->assertSame(
				$legacy_value,
				$case->value,
				"{$api_enum}::{$case->name} and its src/Enums counterpart disagree on the stored value."
			);
			++$checked;
		}

		if ( 0 === $checked ) {
			$this->assertTrue( true, "{$api_enum} has no string-backed case with a src/Enums counterpart." );
		}
	}

	/**
	 * @testdox A src/Enums counterpart declares exactly the values the API enum covers.
	 *
	 * @dataProvider api_enum_provider
	 *
	 * @param class-string $api_enum The API enum under test.
	 */
	public function test_counterpart_constants_are_covered( string $api_enum ): void {
		$legacy = self::API_ENUMS[ $api_enum ]['legacy'];

		if ( null === $legacy ) {
			$this->assertTrue( true, "{$api_enum} has no src/Enums counterpart." );
			return;
		}

		$expected = array_merge(
			array_filter( array_values( self::API_ENUMS[ $api_enum ]['cases'] ) ),
			self::API_ENUMS[ $api_enum ]['legacy_only']
		);
		$actual   = array_values( ( new ReflectionClass( $legacy ) )->getConstants() );

		sort( $expected );
		sort( $actual );

		$this->assertSame(
			$expected,
			$actual,
			"Constants of {$legacy} changed. Update EnumParityTest::API_ENUMS, and check whether {$api_enum} needs the same change."
		);
	}
}
