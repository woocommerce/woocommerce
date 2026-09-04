<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Featured Product block type.
 */
class FeaturedProductTest extends WC_Unit_Test_Case {

	/**
	 * Product created for tests.
	 *
	 * @var \WC_Product|null
	 */
	private $product;

	/**
	 * Attachment IDs created during tests.
	 *
	 * @var int[]
	 */
	private $attachment_ids = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->product ) {
			$this->product->delete( true );
			$this->product = null;
		}

		foreach ( $this->attachment_ids as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
		$this->attachment_ids = array();

		parent::tearDown();
	}

	/**
	 * @testdox Should render the merchant-selected custom image when Image Fit is none.
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/66765.
	 */
	public function test_renders_custom_media_image_when_image_fit_is_none(): void {
		$product_image_id = $this->create_attachment();
		$custom_image_id  = $this->create_attachment();

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_image_id( $product_image_id );
		$this->product->save();

		$custom_image_url  = wp_get_attachment_image_url( $custom_image_id, 'large' );
		$product_image_url = wp_get_attachment_image_url( $product_image_id, 'large' );

		$output = $this->render_featured_product(
			array(
				'productId'   => $this->product->get_id(),
				'mediaId'     => $custom_image_id,
				'imageFit'    => 'none',
				'isRepeated'  => false,
				'hasParallax' => false,
			)
		);

		$this->assertStringContainsString(
			esc_url( $custom_image_url ),
			$output,
			'Custom mediaId image should be used when imageFit is none.'
		);
		$this->assertStringNotContainsString(
			esc_url( $product_image_url ),
			$output,
			'Product image should not be used when a custom mediaId is set.'
		);
	}

	/**
	 * Render a Featured Product block with the given attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private function render_featured_product( array $attributes ): string {
		return do_blocks(
			sprintf(
				'<!-- wp:woocommerce/featured-product %s /-->',
				wp_json_encode( $attributes )
			)
		);
	}

	/**
	 * Create and track a test attachment.
	 *
	 * @return int Attachment ID.
	 */
	private function create_attachment(): int {
		$attachment_id          = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$this->attachment_ids[] = $attachment_id;

		return $attachment_id;
	}
}
