<?php
declare( strict_types = 1 );

/**
 * Tests for `email-order-items.php` template.
 *
 * @covers `email-order-items.php` template
 */
class WC_Email_Order_Items_Test extends \WC_Unit_Test_Case {

	/**
	 * Order IDs created during tests.
	 *
	 * @var int[]
	 */
	private array $order_ids = array();

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->order_ids as $order_id ) {
			WC_Helper_Order::delete_order( $order_id );
		}
		$this->order_ids = array();

		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );

		parent::tearDown();
	}

	/**
	 * @testdox Order item image and text columns use fixed alignment with email improvements enabled.
	 */
	public function test_order_item_image_and_text_columns_use_fixed_alignment_with_email_improvements(): void {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );

		$order             = WC_Helper_Order::create_order();
		$this->order_ids[] = $order->get_id();

		$content = wc_get_email_order_items( $order );

		$this->assertStringContainsString(
			'<table class="order-item-data" role="presentation">',
			$content,
			'Order item data table should fill the product column so rows share the same layout.'
		);
		$this->assertStringContainsString(
			'<td class="email-order-item-thumbnail" style="width: 72px;">',
			$content,
			'Thumbnail column should reserve the image width plus the email improvements image gap.'
		);
	}
}
