<?php
/**
 * Tests for the WC_Admin_Post_Types class.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductType;

/**
 * Class WC_Admin_Post_Types_Test.
 */
class WC_Admin_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Post_Types
	 */
	private $sut;

	/**
	 * The original request data.
	 *
	 * @var array
	 */
	private $original_request;

	/**
	 * The original current user ID.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-post-types.php';

		$reflection             = new ReflectionClass( WC_Admin_Post_Types::class );
		$this->sut              = $reflection->newInstanceWithoutConstructor();
		$this->original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_user_id = get_current_user_id();
		$administrator_user_id  = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator_user_id );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_REQUEST = $this->original_request;
		wp_set_current_user( $this->original_user_id );

		parent::tearDown();
	}

	/**
	 * @testdox Quick Edit explicitly saves active, future, expired, and empty sale schedules for supported product types.
	 * @dataProvider explicit_schedule_provider
	 *
	 * @param string      $product_type Product type.
	 * @param string|null $initial_from Initial sale start date.
	 * @param string|null $initial_to Initial sale end date.
	 * @param string      $submitted_from Submitted sale start date.
	 * @param string      $submitted_to Submitted sale end date.
	 * @param string|null $expected_from Expected sale start date.
	 * @param string|null $expected_to Expected sale end date.
	 */
	public function test_quick_edit_saves_explicit_sale_schedule(
		string $product_type,
		?string $initial_from,
		?string $initial_to,
		string $submitted_from,
		string $submitted_to,
		?string $expected_from,
		?string $expected_to
	): void {
		$product = $this->create_product( $product_type );
		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( $initial_from );
		$product->set_date_on_sale_to( $initial_to );
		$product->save();

		$this->quick_edit(
			$product,
			array(
				'_regular_price'         => '100',
				'_sale_price'            => '70',
				'_sale_price_dates_from' => $submitted_from,
				'_sale_price_dates_to'   => $submitted_to,
			)
		);

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '70', $updated_product->get_sale_price( 'edit' ), 'Quick Edit should persist the submitted sale price.' );
		$this->assert_date( $expected_from, $updated_product->get_date_on_sale_from( 'edit' ), 'start' );
		$this->assert_date( $expected_to, $updated_product->get_date_on_sale_to( 'edit' ), 'end' );
	}

	/**
	 * Provides sale schedule states and supported product types.
	 *
	 * @return array<string, array{string, ?string, ?string, string, string, ?string, ?string}>
	 */
	public static function explicit_schedule_provider(): array {
		return array(
			'active simple schedule'   => array( ProductType::SIMPLE, '2000-01-01 00:00:00', '2099-12-31 23:59:59', '2000-01-01', '2099-12-31', '2000-01-01 00:00:00', '2099-12-31 23:59:59' ),
			'future external schedule' => array( ProductType::EXTERNAL, '2098-01-01 00:00:00', '2098-12-31 23:59:59', '2098-02-01', '2098-11-30', '2098-02-01 00:00:00', '2098-11-30 23:59:59' ),
			'expired simple schedule'  => array( ProductType::SIMPLE, '2000-01-01 00:00:00', '2001-01-01 23:59:59', '2000-01-01', '2001-01-01', '2000-01-01 00:00:00', '2001-01-01 23:59:59' ),
			'valid leap day schedule'  => array( ProductType::SIMPLE, null, null, '2024-02-29', '2024-02-29', '2024-02-29 00:00:00', '2024-02-29 23:59:59' ),
			'empty external schedule'  => array( ProductType::EXTERNAL, null, null, '', '', null, null ),
		);
	}

	/**
	 * @testdox Quick Edit preserves explicit sale dates when only prices are submitted.
	 */
	public function test_quick_edit_price_only_request_preserves_sale_dates(): void {
		$product = $this->create_product( ProductType::SIMPLE );
		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( '2000-01-01 00:00:00' );
		$product->set_date_on_sale_to( '2001-01-01 23:59:59' );
		$product->save();

		$this->quick_edit(
			$product,
			array(
				'_regular_price' => '90',
				'_sale_price'    => '70',
			)
		);

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '90', $updated_product->get_regular_price( 'edit' ), 'Quick Edit should persist the regular price.' );
		$this->assertSame( '70', $updated_product->get_sale_price( 'edit' ), 'Quick Edit should persist the sale price.' );
		$this->assert_date( '2000-01-01 00:00:00', $updated_product->get_date_on_sale_from( 'edit' ), 'start' );
		$this->assert_date( '2001-01-01 23:59:59', $updated_product->get_date_on_sale_to( 'edit' ), 'end' );
	}

	/**
	 * @testdox Quick Edit updates sale dates without requiring price fields.
	 */
	public function test_quick_edit_date_only_request_updates_sale_dates(): void {
		$product = $this->create_product( ProductType::SIMPLE );
		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->save();

		$this->quick_edit(
			$product,
			array(
				'_sale_price_dates_from' => '2098-03-04',
				'_sale_price_dates_to'   => '2098-04-05',
			)
		);

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '100', $updated_product->get_regular_price( 'edit' ), 'Quick Edit should preserve the regular price.' );
		$this->assertSame( '80', $updated_product->get_sale_price( 'edit' ), 'Quick Edit should preserve the sale price.' );
		$this->assert_date( '2098-03-04 00:00:00', $updated_product->get_date_on_sale_from( 'edit' ), 'start' );
		$this->assert_date( '2098-04-05 23:59:59', $updated_product->get_date_on_sale_to( 'edit' ), 'end' );
	}

	/**
	 * @testdox Quick Edit intentionally clears submitted empty sale dates.
	 */
	public function test_quick_edit_clears_sale_dates(): void {
		$product = $this->create_product( ProductType::SIMPLE );
		$product->set_date_on_sale_from( '2000-01-01 00:00:00' );
		$product->set_date_on_sale_to( '2099-12-31 23:59:59' );
		$product->save();

		$this->quick_edit(
			$product,
			array(
				'_sale_price_dates_from' => '',
				'_sale_price_dates_to'   => '',
			)
		);

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertNull( $updated_product->get_date_on_sale_from( 'edit' ), 'Quick Edit should clear the sale start date.' );
		$this->assertNull( $updated_product->get_date_on_sale_to( 'edit' ), 'Quick Edit should clear the sale end date.' );
	}

	/**
	 * @testdox Quick Edit preserves sale dates when submitted values are invalid.
	 * @dataProvider invalid_sale_date_provider
	 *
	 * @param mixed $submitted_date Submitted sale date.
	 */
	public function test_quick_edit_preserves_sale_dates_for_invalid_values( $submitted_date ): void {
		$product = $this->create_product( ProductType::SIMPLE );
		$product->set_date_on_sale_from( '2098-01-01 00:00:00' );
		$product->set_date_on_sale_to( '2098-12-31 23:59:59' );
		$product->save();

		$this->quick_edit(
			$product,
			array(
				'_sale_price_dates_from' => $submitted_date,
				'_sale_price_dates_to'   => $submitted_date,
			)
		);

		$updated_product = wc_get_product( $product->get_id() );

		$this->assert_date( '2098-01-01 00:00:00', $updated_product->get_date_on_sale_from( 'edit' ), 'start' );
		$this->assert_date( '2098-12-31 23:59:59', $updated_product->get_date_on_sale_to( 'edit' ), 'end' );
	}

	/**
	 * Provides invalid sale dates.
	 *
	 * @return array<string, array{mixed}>
	 */
	public static function invalid_sale_date_provider(): array {
		return array(
			'unparseable string' => array( 'tomorow' ),
			'non-string value'   => array( array( '2025-02-28' ) ),
			'invalid leap day'   => array( '2025-02-29' ),
			'invalid June day'   => array( '2025-06-31' ),
			'zero month'         => array( '2025-00-30' ),
			'month without zero' => array( '2025-2-03' ),
			'time suffix'        => array( '2025-02-03 00:00:00' ),
			'whitespace only'    => array( ' ' ),
			'padded date'        => array( ' 2025-02-03 ' ),
		);
	}

	/**
	 * @testdox Quick Edit stores sale boundaries in the site timezone.
	 */
	public function test_quick_edit_uses_site_timezone_for_sale_dates(): void {
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$product = $this->create_product( ProductType::EXTERNAL );
			$this->quick_edit(
				$product,
				array(
					'_sale_price_dates_from' => '2098-06-01',
					'_sale_price_dates_to'   => '2098-06-30',
				)
			);

			$updated_product = wc_get_product( $product->get_id() );
			$start_date      = $updated_product->get_date_on_sale_from( 'edit' );
			$end_date        = $updated_product->get_date_on_sale_to( 'edit' );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}

		$this->assertInstanceOf( WC_DateTime::class, $start_date, 'Quick Edit should set the sale start date.' );
		$this->assertInstanceOf( WC_DateTime::class, $end_date, 'Quick Edit should set the sale end date.' );
		$this->assertSame( '2098-06-01 00:00:00', $start_date->date( 'Y-m-d H:i:s' ), 'The sale should start at the beginning of the local day.' );
		$this->assertSame( '2098-06-30 23:59:59', $end_date->date( 'Y-m-d H:i:s' ), 'The sale should end at the end of the local day.' );
		$this->assertSame( 'America/New_York', $start_date->getTimezone()->getName(), 'The start date should use the site timezone.' );
		$this->assertSame( 'America/New_York', $end_date->getTimezone()->getName(), 'The end date should use the site timezone.' );
	}

	/**
	 * Create a supported product type.
	 *
	 * @param string $product_type Product type.
	 * @return WC_Product
	 */
	private function create_product( string $product_type ): WC_Product {
		return ProductType::EXTERNAL === $product_type ? WC_Helper_Product::create_external_product() : WC_Helper_Product::create_simple_product();
	}

	/**
	 * Submit a Quick Edit request.
	 *
	 * @param WC_Product          $product Product to edit.
	 * @param array<string,mixed> $request_data Request fields.
	 */
	private function quick_edit( WC_Product $product, array $request_data ): void {
		$_REQUEST = array_merge(
			array(
				'woocommerce_quick_edit'       => '1',
				'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
				'_stock_status'                => 'instock',
			),
			$request_data
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );
	}

	/**
	 * Assert a stored sale date.
	 *
	 * @param string|null      $expected Expected local date, or null.
	 * @param WC_DateTime|null $actual Stored date.
	 * @param string           $boundary Boundary name for assertion messages.
	 */
	private function assert_date( ?string $expected, ?WC_DateTime $actual, string $boundary ): void {
		if ( null === $expected ) {
			$this->assertNull( $actual, "Quick Edit should leave the sale {$boundary} date empty." );
			return;
		}

		$this->assertInstanceOf( WC_DateTime::class, $actual, "Quick Edit should set the sale {$boundary} date." );
		$this->assertSame( $expected, $actual->date( 'Y-m-d H:i:s' ), "Quick Edit should set the expected sale {$boundary} boundary." );
	}
}
