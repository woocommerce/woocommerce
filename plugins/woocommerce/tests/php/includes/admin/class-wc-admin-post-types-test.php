<?php
/**
 * Tests for the WC_Admin_Post_Types class.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

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
	 * @testdox Quick Edit preserves sale dates when a valid product price changes.
	 * @dataProvider valid_price_changes_provider
	 *
	 * @param int    $start_offset Sale start offset from the current time.
	 * @param int    $end_offset Sale end offset from the current time.
	 * @param string $new_regular_price New regular price.
	 * @param string $new_sale_price New sale price.
	 */
	public function test_quick_edit_preserves_sale_dates_when_prices_change( int $start_offset, int $end_offset, string $new_regular_price, string $new_sale_price ): void {
		$now                = time();
		$product            = WC_Helper_Product::create_simple_product();
		$sale_date_from     = gmdate( 'Y-m-d H:i:s', $now + $start_offset );
		$sale_date_to       = gmdate( 'Y-m-d H:i:s', $now + $end_offset );
		$original_sale_from = wc_string_to_datetime( $sale_date_from );
		$original_sale_to   = wc_string_to_datetime( $sale_date_to );

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( $sale_date_from );
		$product->set_date_on_sale_to( $sale_date_to );
		$product->save();

		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_regular_price'               => $new_regular_price,
			'_sale_price'                  => $new_sale_price,
			'_stock_status'                => 'instock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated_product   = wc_get_product( $product->get_id() );
		$updated_sale_from = $updated_product->get_date_on_sale_from( 'edit' );
		$updated_sale_to   = $updated_product->get_date_on_sale_to( 'edit' );

		$this->assertSame( $new_regular_price, $updated_product->get_regular_price( 'edit' ), 'Quick Edit should persist the regular price.' );
		$this->assertSame( $new_sale_price, $updated_product->get_sale_price( 'edit' ), 'Quick Edit should persist the sale price.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_sale_from, 'Quick Edit should preserve the sale start date.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_sale_to, 'Quick Edit should preserve the sale end date.' );
		$this->assertSame( $original_sale_from->getTimestamp(), $updated_sale_from->getTimestamp(), 'The sale start date should remain unchanged.' );
		$this->assertSame( $original_sale_to->getTimestamp(), $updated_sale_to->getTimestamp(), 'The sale end date should remain unchanged.' );
	}

	/**
	 * @testdox Quick Edit clears sale dates when a price change ends the sale.
	 * @dataProvider sale_ending_price_changes_provider
	 *
	 * @param string $new_regular_price New regular price.
	 * @param string $new_sale_price New sale price.
	 */
	public function test_quick_edit_clears_sale_dates_when_sale_ends( string $new_regular_price, string $new_sale_price ): void {
		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
		$product->save();

		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_regular_price'               => $new_regular_price,
			'_sale_price'                  => $new_sale_price,
			'_stock_status'                => 'instock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '', $updated_product->get_sale_price( 'edit' ), 'Quick Edit should leave the product without a sale price.' );
		$this->assertNull( $updated_product->get_date_on_sale_from( 'edit' ), 'Quick Edit should clear the start date when the sale ends.' );
		$this->assertNull( $updated_product->get_date_on_sale_to( 'edit' ), 'Quick Edit should clear the end date when the sale ends.' );
	}

	/**
	 * @testdox Quick Edit clears expired sale dates when prices change so the new sale takes effect.
	 * @dataProvider expired_schedule_price_changes_provider
	 *
	 * @param string $new_regular_price New regular price.
	 * @param string $new_sale_price New sale price.
	 */
	public function test_quick_edit_clears_expired_sale_dates_when_prices_change( string $new_regular_price, string $new_sale_price ): void {
		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->save();

		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_regular_price'               => $new_regular_price,
			'_sale_price'                  => $new_sale_price,
			'_stock_status'                => 'instock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( $new_regular_price, $updated_product->get_regular_price( 'edit' ), 'Quick Edit should persist the regular price.' );
		$this->assertSame( $new_sale_price, $updated_product->get_sale_price( 'edit' ), 'Quick Edit should persist the sale price.' );
		$this->assertNull( $updated_product->get_date_on_sale_from( 'edit' ), 'Quick Edit should clear the expired sale start date.' );
		$this->assertNull( $updated_product->get_date_on_sale_to( 'edit' ), 'Quick Edit should clear the expired sale end date.' );
		$this->assertTrue( $updated_product->is_on_sale( 'edit' ), 'The product should be on sale once the expired schedule is cleared.' );
		$this->assertSame( $new_sale_price, $updated_product->get_price( 'edit' ), 'The active price should be the new sale price.' );
	}

	/**
	 * @testdox Quick Edit preserves expired sale dates when prices are not edited, even if display filters reformat prices.
	 */
	public function test_quick_edit_preserves_expired_sale_dates_when_prices_not_edited(): void {
		// Simulates extensions (currency switchers, wholesale, dynamic pricing) that filter
		// prices in view context, reshaping the values the Quick Edit form is prefilled with.
		$format_price = function ( $value ) {
			return '' === $value || null === $value ? $value : number_format( (float) $value, 2, '.', '' );
		};
		add_filter( 'woocommerce_product_get_regular_price', $format_price );
		add_filter( 'woocommerce_product_get_sale_price', $format_price );

		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->save();

		// A stock-only Quick Edit: the price inputs hold the filtered display values, submitted back unchanged.
		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_regular_price'               => '100.00',
			'_sale_price'                  => '80.00',
			'_stock_status'                => 'outofstock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		remove_filter( 'woocommerce_product_get_regular_price', $format_price );
		remove_filter( 'woocommerce_product_get_sale_price', $format_price );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '100.00', $updated_product->get_regular_price( 'edit' ), 'The submitted display-formatted regular price is what gets persisted.' );
		$this->assertSame( '80.00', $updated_product->get_sale_price( 'edit' ), 'The submitted display-formatted sale price is what gets persisted.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_product->get_date_on_sale_from( 'edit' ), 'Quick Edit should preserve the expired sale start date when no price was edited.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_product->get_date_on_sale_to( 'edit' ), 'Quick Edit should preserve the expired sale end date when no price was edited.' );
		$this->assertFalse( $updated_product->is_on_sale( 'edit' ), 'The expired sale should not be reactivated by an edit that did not touch prices.' );
	}

	/**
	 * @testdox Quick Edit preserves expired sale dates when a converting display filter round-trips unedited prices.
	 */
	public function test_quick_edit_preserves_expired_sale_dates_with_converting_filter(): void {
		// Simulates a value-converting filter (currency switcher, dynamic pricing) that is
		// active during both the form render and the save request.
		$convert_price = function ( $value ) {
			return '' === $value || null === $value ? $value : (string) ( 2 * (float) $value );
		};
		add_filter( 'woocommerce_product_get_regular_price', $convert_price );
		add_filter( 'woocommerce_product_get_sale_price', $convert_price );

		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->save();

		// A stock-only Quick Edit: the form displayed the converted values and submits them back unchanged.
		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_regular_price'               => '200',
			'_sale_price'                  => '160',
			'_stock_status'                => 'outofstock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		remove_filter( 'woocommerce_product_get_regular_price', $convert_price );
		remove_filter( 'woocommerce_product_get_sale_price', $convert_price );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertInstanceOf( WC_DateTime::class, $updated_product->get_date_on_sale_from( 'edit' ), 'Quick Edit should preserve the expired sale start date under a converting display filter.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_product->get_date_on_sale_to( 'edit' ), 'Quick Edit should preserve the expired sale end date under a converting display filter.' );
		$this->assertFalse( $updated_product->is_on_sale( 'edit' ), 'The expired sale should not be reactivated by a round-trip of converted display prices.' );
	}

	/**
	 * @testdox Quick Edit survives a display filter returning a non-scalar value.
	 */
	public function test_quick_edit_survives_non_scalar_price_filter(): void {
		$break_price = function () {
			return new stdClass();
		};
		add_filter( 'woocommerce_product_get_regular_price', $break_price );
		add_filter( 'woocommerce_product_get_sale_price', $break_price );

		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->save();

		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_regular_price'               => '90',
			'_sale_price'                  => '70',
			'_stock_status'                => 'instock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		remove_filter( 'woocommerce_product_get_regular_price', $break_price );
		remove_filter( 'woocommerce_product_get_sale_price', $break_price );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '90', $updated_product->get_regular_price( 'edit' ), 'Submitted prices should persist even when a display filter returns a non-scalar value.' );
		$this->assertSame( '70', $updated_product->get_sale_price( 'edit' ), 'Submitted prices should persist even when a display filter returns a non-scalar value.' );
	}

	/**
	 * @testdox Quick Edit preserves expired sale dates when price fields are absent from the request.
	 */
	public function test_quick_edit_preserves_expired_sale_dates_when_price_fields_not_submitted(): void {
		$format_price = function ( $value ) {
			return '' === $value || null === $value ? $value : number_format( (float) $value, 2, '.', '' );
		};
		add_filter( 'woocommerce_product_get_regular_price', $format_price );
		add_filter( 'woocommerce_product_get_sale_price', $format_price );

		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->save();

		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_stock_status'                => 'instock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		remove_filter( 'woocommerce_product_get_regular_price', $format_price );
		remove_filter( 'woocommerce_product_get_sale_price', $format_price );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '100', $updated_product->get_regular_price( 'edit' ), 'An absent regular price field should leave the stored regular price untouched.' );
		$this->assertSame( '80', $updated_product->get_sale_price( 'edit' ), 'An absent sale price field should leave the stored sale price untouched.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_product->get_date_on_sale_from( 'edit' ), 'Quick Edit should preserve the expired sale start date when no price fields were submitted.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_product->get_date_on_sale_to( 'edit' ), 'Quick Edit should preserve the expired sale end date when no price fields were submitted.' );
		$this->assertFalse( $updated_product->is_on_sale( 'edit' ), 'The expired sale should not be reactivated by a request without price fields.' );
	}

	/**
	 * Provides valid price changes for active and future sale schedules.
	 *
	 * @return array<string, array{int, int, string, string}>
	 */
	public static function valid_price_changes_provider(): array {
		return array(
			'active schedule with sale price change'    => array( -DAY_IN_SECONDS, DAY_IN_SECONDS, '100', '70' ),
			'future schedule with sale price change'    => array( DAY_IN_SECONDS, 2 * DAY_IN_SECONDS, '100', '70' ),
			'active schedule with regular price change' => array( -DAY_IN_SECONDS, DAY_IN_SECONDS, '90', '80' ),
		);
	}

	/**
	 * Provides price changes for a product whose scheduled sale has already ended.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function expired_schedule_price_changes_provider(): array {
		return array(
			'expired schedule with new sale price'       => array( '100', '70' ),
			'expired schedule with regular price change' => array( '90', '80' ),
		);
	}

	/**
	 * Provides price changes that end an active sale.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function sale_ending_price_changes_provider(): array {
		return array(
			'regular price no longer above sale price' => array( '70', '80' ),
			'sale price removed'                       => array( '100', '' ),
		);
	}
}
