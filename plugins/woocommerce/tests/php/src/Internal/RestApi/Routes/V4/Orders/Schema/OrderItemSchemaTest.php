<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Orders\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderItemSchema;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the OrderItemSchema class.
 */
class OrderItemSchemaTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderItemSchema
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderItemSchema();
	}

	/**
	 * @testdox The item image is looked up with an integer attachment ID.
	 */
	public function test_item_image_passes_an_integer_attachment_id_to_wordpress(): void {
		$image_id = self::factory()->post->create( array( 'post_type' => 'attachment' ) );
		$product  = WC_Helper_Product::create_simple_product( false );
		$product->set_image_id( $image_id );
		$product = wc_get_product( $product->save() );
		$order   = WC_Helper_Order::create_order( 1, $product );
		$item    = current( $order->get_items() );

		$received_id = null;
		add_filter(
			'wp_get_attachment_image_src',
			static function ( $image, $attachment_id ) use ( &$received_id ) {
				if ( null === $received_id ) {
					$received_id = $attachment_id;
				}
				return $image;
			},
			10,
			2
		);

		$this->sut->get_item_response( $item, new WP_REST_Request( 'GET', '/wc/v4/orders' ) );

		$this->assertSame( $image_id, $received_id, 'The attachment ID passed to WordPress should be an integer.' );
	}
}
