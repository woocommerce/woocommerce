<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;

/**
 * Tests for the ProductGallery block type
 */
class ProductGallery extends \WP_UnitTestCase {

	/**
	 * Previous thumbnail cropping option values to restore after tests.
	 *
	 * @var array<string, mixed>
	 */
	private $prev_thumbnail_cropping_options = array();

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$option_names = array(
			'woocommerce_thumbnail_cropping',
			'woocommerce_thumbnail_cropping_custom_width',
			'woocommerce_thumbnail_cropping_custom_height',
		);

		foreach ( $option_names as $option_name ) {
			$this->prev_thumbnail_cropping_options[ $option_name ] = get_option( $option_name, null );
		}
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		foreach ( $this->prev_thumbnail_cropping_options as $option_name => $value ) {
			if ( null === $value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $value );
			}
		}

		parent::tearDown();
	}

	/**
	 * Helper method to create a product with multiple images.
	 *
	 * @param int $gallery_count Number of gallery images to create.
	 * @return array Array containing 'product', 'main_image_id', and 'gallery_image_ids'.
	 */
	private function create_product_with_gallery( $gallery_count = 3 ) {
		$product = WC_Helper_Product::create_simple_product();

		// Create and set the main product image.
		$main_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Main Product Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $main_image_id );

		// Create gallery images.
		$gallery_image_ids = array();
		for ( $i = 0; $i < $gallery_count; $i++ ) {
			$gallery_image_ids[] = wp_insert_attachment(
				array(
					'post_title'     => 'Gallery Image ' . ( $i + 1 ),
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			);
		}
		$product->set_gallery_image_ids( $gallery_image_ids );
		$product->save();

		return array(
			'product'           => $product,
			'main_image_id'     => $main_image_id,
			'gallery_image_ids' => $gallery_image_ids,
		);
	}

	/**
	 * Helper method to render the product gallery block.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $gallery_attributes Optional gallery attributes.
	 * @param array  $product_image_attributes Optional Product Image block attributes.
	 * @return string The rendered markup.
	 */
	private function render_product_gallery(
		$product_id,
		$gallery_attributes = '',
		$product_image_attributes = array()
	) {
		$product_image_attributes = wp_json_encode(
			array_merge(
				array(
					'showProductLink'                  => false,
					'showSaleBadge'                    => false,
					'isDescendentOfSingleProductBlock' => true,
				),
				$product_image_attributes
			)
		);

		return do_blocks(
			sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%d} -->
				<div class="wp-block-woocommerce-single-product woocommerce">
					<!-- wp:woocommerce/product-gallery %s -->
					<div class="wp-block-woocommerce-product-gallery wc-block-product-gallery">
						<!-- wp:woocommerce/product-gallery-thumbnails /-->

						<!-- wp:woocommerce/product-gallery-large-image -->
						<div class="wp-block-woocommerce-product-gallery-large-image wc-block-product-gallery-large-image__inner-blocks">
							<!-- wp:woocommerce/product-image %s /-->

							<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->

							<!-- wp:woocommerce/product-gallery-large-image-next-previous -->
							<div class="wp-block-woocommerce-product-gallery-large-image-next-previous"></div>
							<!-- /wp:woocommerce/product-gallery-large-image-next-previous -->
						</div>
						<!-- /wp:woocommerce/product-gallery-large-image -->
					</div>
					<!-- /wp:woocommerce/product-gallery -->
				</div>
				<!-- /wp:woocommerce/single-product -->',
				$product_id,
				$gallery_attributes,
				$product_image_attributes
			)
		);
	}

	/**
	 * Helper method to clean up product and image data.
	 *
	 * @param array|WC_Product $data Product data array or WC_Product instance.
	 */
	private function cleanup_product_data( $data ) {
		if ( is_array( $data ) ) {
			$data['product']->delete( true );
			wp_delete_attachment( $data['main_image_id'], true );
			foreach ( $data['gallery_image_ids'] as $gallery_image_id ) {
				wp_delete_attachment( $gallery_image_id, true );
			}
		} elseif ( is_object( $data ) && is_a( $data, 'WC_Product' ) ) {
			$data->delete( true );
		}
	}

	/**
	 * Assert that rendered gallery markup contains the expected large image aspect ratio CSS variables.
	 *
	 * @param string $markup Rendered gallery markup.
	 * @param string $width Expected ratio width.
	 * @param string $height Expected ratio height.
	 */
	private function assert_product_gallery_large_image_ratio_variables( string $markup, string $width, string $height ): void {
		$this->assertStringContainsString(
			"--wc-block-product-gallery-large-image-ratio-width:{$width};",
			$markup,
			"Expected Product Gallery markup to expose a {$width}:{$height} large image aspect ratio width variable."
		);
		$this->assertStringContainsString(
			"--wc-block-product-gallery-large-image-ratio-height:{$height};",
			$markup,
			"Expected Product Gallery markup to expose a {$width}:{$height} large image aspect ratio height variable."
		);
	}

	/**
	 * Test that the ProductGallery block renders correctly with multiple images.
	 */
	public function test_product_gallery_render_with_multiple_images() {
		$data = $this->create_product_with_gallery( 3 );

		$markup = $this->render_product_gallery( $data['product']->get_id() );

		// Check that the gallery wrapper is rendered.
		$this->assertStringContainsString( 'wc-block-product-gallery', $markup );

		// Check that the viewer block is rendered.
		$this->assertStringContainsString( 'wc-block-product-gallery-large-image', $markup );

		// Check that the thumbnails block is rendered.
		$this->assertStringContainsString( 'wc-block-product-gallery-thumbnails', $markup );

		// Check that all images are rendered (main image + gallery images).
		$this->assertStringContainsString( 'data-image-id="' . $data['main_image_id'] . '"', $markup );
		foreach ( $data['gallery_image_ids'] as $gallery_image_id ) {
			$this->assertStringContainsString( 'data-image-id="' . $gallery_image_id . '"', $markup );
		}

		// Check that the aspect ratio class is applied.
		$this->assertStringContainsString( 'wc-block-components-product-image--aspect-ratio-auto', $markup );

		$this->cleanup_product_data( $data );
	}

	/**
	 * @testdox Should expose Product Image style aspect ratios as large image CSS variables.
	 */
	public function test_product_gallery_exposes_custom_product_image_aspect_ratio_css_variables(): void {
		$data = $this->create_product_with_gallery( 3 );

		$markup = $this->render_product_gallery(
			$data['product']->get_id(),
			'',
			array(
				'style' => array(
					'dimensions' => array(
						'aspectRatio' => '3/5',
					),
				),
			)
		);

		$this->assert_product_gallery_large_image_ratio_variables( $markup, '3', '5' );

		$this->cleanup_product_data( $data );
	}

	/**
	 * @testdox Should expose Product Image aspectRatio attributes as large image CSS variables.
	 */
	public function test_product_gallery_exposes_legacy_product_image_aspect_ratio_css_variables(): void {
		$data = $this->create_product_with_gallery( 3 );

		$markup = $this->render_product_gallery(
			$data['product']->get_id(),
			'',
			array(
				'aspectRatio' => '7/4',
			)
		);

		$this->assert_product_gallery_large_image_ratio_variables( $markup, '7', '4' );

		$this->cleanup_product_data( $data );
	}

	/**
	 * @testdox Should use custom store thumbnail ratios for Product Image thumbnail sizing.
	 * @dataProvider product_image_thumbnail_sizing_data
	 *
	 * @param string $image_sizing Product Image sizing mode.
	 */
	public function test_product_gallery_uses_store_thumbnail_ratio_for_thumbnail_product_image_sizing( string $image_sizing ): void {
		$data = $this->create_product_with_gallery( 3 );

		update_option( 'woocommerce_thumbnail_cropping', 'custom' );
		update_option( 'woocommerce_thumbnail_cropping_custom_width', '5' );
		update_option( 'woocommerce_thumbnail_cropping_custom_height', '8' );

		$markup = $this->render_product_gallery(
			$data['product']->get_id(),
			'',
			array(
				'imageSizing' => $image_sizing,
			)
		);

		$this->assert_product_gallery_large_image_ratio_variables( $markup, '5', '8' );

		$this->cleanup_product_data( $data );
	}

	/**
	 * Data provider for Product Image thumbnail sizing modes.
	 *
	 * @return array[]
	 */
	public function product_image_thumbnail_sizing_data(): array {
		return array(
			'thumbnail sizing' => array( 'thumbnail' ),
			'cropped sizing'   => array( 'cropped' ),
		);
	}

	/**
	 * Test that the ProductGallery block renders correctly with hover zoom enabled.
	 */
	public function test_product_gallery_render_with_hover_zoom() {
		$data = $this->create_product_with_gallery( 1 );

		$markup = $this->render_product_gallery(
			$data['product']->get_id(),
			'{"hoverZoom":true}'
		);

		// Check that hover zoom is enabled in the context.
		$this->assertStringContainsString( 'data-hover-zoom="true"', $markup );

		$this->cleanup_product_data( $data );
	}

	/**
	 * Test that the ProductGallery block renders correctly with fullscreen on click enabled.
	 */
	public function test_product_gallery_render_with_fullscreen_on_click() {
		$data = $this->create_product_with_gallery( 1 );

		$markup = $this->render_product_gallery(
			$data['product']->get_id(),
			'{"fullScreenOnClick":true}'
		);

		// Check that fullscreen is enabled in the context.
		$this->assertStringContainsString( 'data-full-screen-on-click="true"', $markup );

		$this->cleanup_product_data( $data );
	}

	/**
	 * Test that the ProductGallery block handles products without images correctly.
	 */
	public function test_product_gallery_render_without_images() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$markup = $this->render_product_gallery( $product->get_id() );

		// Should contain placeholder image.
		$this->assertStringContainsString( 'woocommerce-placeholder', $markup );

		$this->cleanup_product_data( $product );
	}

	/**
	 * Test that the ProductGallery block handles invalid product IDs correctly.
	 */
	public function test_product_gallery_render_with_invalid_product() {
		$markup = $this->render_product_gallery( 99999 );

		$this->assertEmpty( $markup );
	}
}
