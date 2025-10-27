<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Class WC_Order_Note_Test.
 */
class WC_Order_Note_Test extends \WC_Unit_Test_Case {

	use HPOSToggleTrait;

	/**
	 * @var bool Was HPOS enabled before the test?
	 */
	private $prev_hpos_enabled;

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->prev_hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		$this->setup_cot();
	}

	/**
	 * Teardown test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		$this->toggle_cot_feature_and_usage( $this->prev_hpos_enabled );
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );

		parent::tearDown();
	}

	/**
	 * Test add_order_note method with meta_data parameter.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether to test with HPOS enabled or not.
	 */
	public function test_add_order_note_with_meta_data( $hpos_enabled ) {
		$this->toggle_cot_authoritative( $hpos_enabled );

		// Create an order.
		$order = wc_create_order();
		$order->save();

		// Test meta_data with custom note_group and note_title.
		$meta_data = array(
			'note_group'   => 'payment',
			'note_title'   => 'Payment processed',
			'custom_field' => 'custom_value',
		);

		$note_id = $order->add_order_note( 'Test note with meta data', false, false, $meta_data );

		// Verify note was created.
		$this->assertGreaterThan( 0, $note_id );

		// Verify meta_data was saved.
		$this->assertEquals( 'payment', get_comment_meta( $note_id, 'note_group', true ) );
		$this->assertEquals( 'Payment processed', get_comment_meta( $note_id, 'note_title', true ) );
		$this->assertEquals( 'custom_value', get_comment_meta( $note_id, 'custom_field', true ) );
	}
}
