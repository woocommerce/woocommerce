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

	/**
	 * @testdox Order item images are not lazy loaded while an email is sending.
	 */
	public function test_order_item_image_is_not_lazy_loaded_while_sending(): void {
		$order             = WC_Helper_Order::create_order();
		$this->order_ids[] = $order->get_id();

		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => '2026/11/image.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		wp_update_attachment_metadata(
			$attachment_id,
			array(
				'width'  => 48,
				'height' => 48,
				'file'   => '2026/11/image.jpg',
			)
		);
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$product->set_image_id( $attachment_id );
			$product->save();
		}

		$email          = new WC_Email();
		$email->sending = true;
		$content        = wc_get_email_order_items( $order, array( 'show_image' => true ) );
		$email->sending = false;

		$this->assertStringContainsString( '<img', $content );
		$this->assertStringNotContainsString( 'loading=', $content );
	}

	/**
	 * @testdox Web-only attributes are removed from attachment images while an email is sending.
	 */
	public function test_web_only_attributes_are_removed_while_sending(): void {
		$email          = new WC_Email();
		$email->sending = true;

		$attributes     = $email->prevent_lazy_loading_on_attachment(
			array(
				'src'           => 'https://example.com/image.jpg',
				'loading'       => 'lazy',
				'decoding'      => 'async',
				'fetchpriority' => 'high',
				'srcset'        => 'https://example.com/image.jpg 300w',
				'sizes'         => '100vw',
			)
		);
		$email->sending = false;

		$this->assertSame( 'https://example.com/image.jpg', $attributes['src'] );
		$this->assertEmpty( array_intersect_key( $attributes, array_flip( array( 'loading', 'decoding', 'fetchpriority', 'srcset', 'sizes' ) ) ) );
	}
}
