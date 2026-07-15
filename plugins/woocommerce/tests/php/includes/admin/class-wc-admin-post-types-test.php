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
	 * @testdox Quick Edit preserves sale dates when the sale price changes.
	 * @dataProvider sale_schedule_offsets_provider
	 *
	 * @param int $start_offset Sale start offset from the current time.
	 * @param int $end_offset Sale end offset from the current time.
	 */
	public function test_quick_edit_preserves_sale_dates_when_sale_price_changes( int $start_offset, int $end_offset ): void {
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
			'_regular_price'               => '100',
			'_sale_price'                  => '70',
			'_stock_status'                => 'instock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated_product   = wc_get_product( $product->get_id() );
		$updated_sale_from = $updated_product->get_date_on_sale_from( 'edit' );
		$updated_sale_to   = $updated_product->get_date_on_sale_to( 'edit' );

		$this->assertSame( '70', $updated_product->get_sale_price( 'edit' ), 'Quick Edit should persist the updated sale price.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_sale_from, 'Quick Edit should preserve the sale start date.' );
		$this->assertInstanceOf( WC_DateTime::class, $updated_sale_to, 'Quick Edit should preserve the sale end date.' );
		$this->assertSame( $original_sale_from->getTimestamp(), $updated_sale_from->getTimestamp(), 'The sale start date should remain unchanged.' );
		$this->assertSame( $original_sale_to->getTimestamp(), $updated_sale_to->getTimestamp(), 'The sale end date should remain unchanged.' );
	}

	/**
	 * @testdox Quick Edit clears sale dates when a price change invalidates the sale.
	 */
	public function test_quick_edit_clears_sale_dates_when_regular_price_invalidates_sale(): void {
		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( '100' );
		$product->set_sale_price( '80' );
		$product->set_date_on_sale_from( gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) );
		$product->set_date_on_sale_to( gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
		$product->save();

		$_REQUEST = array(
			'woocommerce_quick_edit'       => '1',
			'woocommerce_quick_edit_nonce' => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'_regular_price'               => '70',
			'_sale_price'                  => '80',
			'_stock_status'                => 'instock',
		);

		$this->sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( '', $updated_product->get_sale_price( 'edit' ), 'Quick Edit should clear a sale price that is not below the regular price.' );
		$this->assertNull( $updated_product->get_date_on_sale_from( 'edit' ), 'Quick Edit should clear the start date when the sale price is invalidated.' );
		$this->assertNull( $updated_product->get_date_on_sale_to( 'edit' ), 'Quick Edit should clear the end date when the sale price is invalidated.' );
	}

	/**
	 * Provides active and future sale schedule offsets.
	 *
	 * @return array<string, array{int, int}>
	 */
	public static function sale_schedule_offsets_provider(): array {
		return array(
			'active sale schedule' => array( -DAY_IN_SECONDS, DAY_IN_SECONDS ),
			'future sale schedule' => array( DAY_IN_SECONDS, 2 * DAY_IN_SECONDS ),
		);
	}
}
