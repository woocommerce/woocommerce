<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;

/**
 * Tests for the ProductImageGallery block type.
 */
class ProductImageGallery extends \WP_UnitTestCase {

	/**
	 * Theme support flags that should be restored after each test.
	 *
	 * @var string[]
	 */
	private $theme_support_flags = array(
		'wc-product-gallery-zoom',
		'wc-product-gallery-slider',
		'wc-product-gallery-lightbox',
	);

	/**
	 * Snapshot of which flags were enabled before the test.
	 *
	 * @var array<string, bool>
	 */
	private $theme_support_snapshot = array();

	/**
	 * Set up theme support snapshot so each test can mutate freely.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( $this->theme_support_flags as $flag ) {
			$this->theme_support_snapshot[ $flag ] = (bool) current_theme_supports( $flag );
		}
	}

	/**
	 * Restore theme support and filters after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->theme_support_flags as $flag ) {
			if ( $this->theme_support_snapshot[ $flag ] ) {
				add_theme_support( $flag );
			} else {
				remove_theme_support( $flag );
			}
		}

		remove_all_filters( 'woocommerce_single_product_zoom_enabled' );
		remove_all_filters( 'woocommerce_single_product_photoswipe_enabled' );
		remove_all_filters( 'woocommerce_single_product_flexslider_enabled' );

		parent::tearDown();
	}

	/**
	 * Create a simple product with a featured image so the gallery has something to render.
	 *
	 * @return array{product: \WC_Product, image_id: int}
	 */
	private function create_product_with_image(): array {
		$product  = WC_Helper_Product::create_simple_product();
		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Gallery Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $image_id );
		$product->save();

		return array(
			'product'  => $product,
			'image_id' => $image_id,
		);
	}

	/**
	 * Render the block and return whether each gallery filter resolves to true after render.
	 *
	 * @param int $product_id Product to render.
	 * @return array{zoom: bool, lightbox: bool, slider: bool}
	 */
	private function render_and_capture_filters( int $product_id ): array {
		do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} --><!-- wp:woocommerce/product-image-gallery /--><!-- /wp:woocommerce/single-product -->' );

		return array(
			'zoom'     => (bool) apply_filters( 'woocommerce_single_product_zoom_enabled', get_theme_support( 'wc-product-gallery-zoom' ) ),
			'lightbox' => (bool) apply_filters( 'woocommerce_single_product_photoswipe_enabled', get_theme_support( 'wc-product-gallery-lightbox' ) ),
			'slider'   => (bool) apply_filters( 'woocommerce_single_product_flexslider_enabled', get_theme_support( 'wc-product-gallery-slider' ) ),
		);
	}

	/**
	 * @testdox Should force gallery filters on when theme support is declared.
	 */
	public function test_render_forces_filters_on_when_theme_supports_features(): void {
		$data = $this->create_product_with_image();

		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'wc-product-gallery-lightbox' );

		$filters = $this->render_and_capture_filters( $data['product']->get_id() );

		$this->assertTrue( $filters['zoom'], 'Zoom should be enabled when theme support is declared.' );
		$this->assertTrue( $filters['slider'], 'Slider should be enabled when theme support is declared.' );
		$this->assertTrue( $filters['lightbox'], 'Lightbox should be enabled when theme support is declared.' );

		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * @testdox Should respect remove_theme_support for gallery zoom and slider when block is rendered.
	 */
	public function test_render_respects_remove_theme_support_for_zoom_and_slider(): void {
		$data = $this->create_product_with_image();

		add_theme_support( 'wc-product-gallery-lightbox' );
		remove_theme_support( 'wc-product-gallery-zoom' );
		remove_theme_support( 'wc-product-gallery-slider' );

		$filters = $this->render_and_capture_filters( $data['product']->get_id() );

		$this->assertFalse(
			$filters['zoom'],
			'Zoom must stay disabled when remove_theme_support( "wc-product-gallery-zoom" ) is in effect. See woocommerce/woocommerce#61248.'
		);
		$this->assertFalse(
			$filters['slider'],
			'Slider must stay disabled when remove_theme_support( "wc-product-gallery-slider" ) is in effect. See woocommerce/woocommerce#61248.'
		);
		$this->assertTrue(
			$filters['lightbox'],
			'Lightbox should remain enabled when its theme support is still declared.'
		);

		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * @testdox Should not force any gallery filter on when all theme support is removed.
	 */
	public function test_render_does_not_force_filters_when_all_theme_support_removed(): void {
		$data = $this->create_product_with_image();

		foreach ( $this->theme_support_flags as $flag ) {
			remove_theme_support( $flag );
		}

		$filters = $this->render_and_capture_filters( $data['product']->get_id() );

		$this->assertFalse( $filters['zoom'], 'Zoom should be disabled when no theme support is declared.' );
		$this->assertFalse( $filters['slider'], 'Slider should be disabled when no theme support is declared.' );
		$this->assertFalse( $filters['lightbox'], 'Lightbox should be disabled when no theme support is declared.' );

		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}
}
