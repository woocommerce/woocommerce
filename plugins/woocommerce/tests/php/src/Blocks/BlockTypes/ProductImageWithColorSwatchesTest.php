<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductImageWithColorSwatchesMock;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductImageWithColorSwatches block type.
 */
class ProductImageWithColorSwatchesTest extends WC_Unit_Test_Case {

	/**
	 * Tracks whether blocks have been registered.
	 *
	 * @var bool
	 */
	protected static $are_blocks_registered = false;

	/**
	 * Register blocks required for do_blocks tests.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! self::$are_blocks_registered && ! \WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/product-image-with-color-swatches' ) ) {
			new ProductImageWithColorSwatchesMock();
		}

		self::$are_blocks_registered = true;
	}

	/**
	 * @testdox Renders color swatches with first variation image for each color.
	 */
	public function test_renders_color_swatches_with_first_variation_image_for_each_color(): void {
		$data = $this->create_variable_product_with_color_variation_images();

		try {
			$markup  = $this->render_block_for_product( $data['product']->get_id() );
			$decoded = html_entity_decode( $markup, ENT_QUOTES );

			$this->assertStringContainsString( 'data-wp-interactive="woocommerce/product-image-with-color-swatches"', $markup, 'Wrapper should register the swatch store namespace.' );
			$this->assertStringContainsString( 'data-wp-bind--src="state.currentImage.src"', $markup, 'Product image should bind src to the selected swatch image.' );
			$this->assertStringContainsString( 'data-wp-bind--data-image-id="state.currentImage.id"', $markup, 'Product image should bind image ID to the selected swatch image.' );
			$this->assertStringContainsString( 'background-color: #aa0000', $markup, 'Red swatch should use term color meta.' );
			$this->assertStringContainsString( 'background-color: #0000aa', $markup, 'Blue swatch should use term color meta.' );
			$this->assertStringContainsString( '"id":' . $data['red_image_id'], $decoded, 'Red swatch should use the first red variation image that has an image.' );
			$this->assertStringContainsString( '"id":' . $data['blue_image_id'], $decoded, 'Blue swatch should use the first blue variation image that has an image.' );
			$this->assertStringNotContainsString( 'red-second.jpg', $decoded, 'Second red variation image should not be used.' );
		} finally {
			$this->cleanup_variable_product_data( $data );
		}
	}

	/**
	 * @testdox Renders only the product image for products without color variation images.
	 */
	public function test_renders_only_product_image_without_color_variation_images(): void {
		$product  = new \WC_Product_Simple();
		$image_id = $this->create_image_attachment( 'Simple image', 'simple.jpg' );
		$product->set_regular_price( 10 );
		$product->set_image_id( $image_id );
		$product->save();

		try {
			$markup = $this->render_block_for_product( $product->get_id() );

			$this->assertStringContainsString( 'wc-block-components-product-image', $markup, 'Product image should still render.' );
			$this->assertStringNotContainsString( 'wc-block-product-filter-chips', $markup, 'Chips should not render without color variation images.' );
			$this->assertStringNotContainsString( 'data-wp-interactive="woocommerce/product-image-with-color-swatches"', $markup, 'Wrapper should not initialize swatch interactivity without items.' );
		} finally {
			$product->delete( true );
			wp_delete_attachment( $image_id, true );
		}
	}

	/**
	 * Create a variable product with color variation images.
	 *
	 * @return array<string, mixed> Test data.
	 */
	private function create_variable_product_with_color_variation_images(): array {
		$fixtures  = new FixtureData();
		$attribute = FixtureData::get_product_attribute( 'pcswatch', array( 'red', 'blue' ) );
		$taxonomy  = $attribute['attribute_taxonomy'];
		$term_red  = get_term( $attribute['term_ids'][0] );
		$term_blue = get_term( $attribute['term_ids'][1] );

		$this->assertInstanceOf( \WP_Term::class, $term_red );
		$this->assertInstanceOf( \WP_Term::class, $term_blue );

		$this->set_attribute_type( (int) $attribute['attribute_id'], 'wc-visual' );
		update_term_meta( $term_red->term_id, 'color', '#aa0000' );
		update_term_meta( $term_blue->term_id, 'color', '#0000aa' );

		$product = $fixtures->get_variable_product( array(), array( $attribute ) );
		$this->assertInstanceOf( \WC_Product_Variable::class, $product );

		$parent_image_id = $this->create_image_attachment( 'Parent image', 'parent.jpg' );
		$red_image_id    = $this->create_image_attachment( 'Red image', 'red-first.jpg' );
		$red_second_id   = $this->create_image_attachment( 'Second red image', 'red-second.jpg' );
		$blue_image_id   = $this->create_image_attachment( 'Blue image', 'blue.jpg' );

		$product->set_image_id( $parent_image_id );
		$product->save();

		$fixtures->get_variation_product(
			$product->get_id(),
			array( $taxonomy => $term_red->slug ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		$red_variation = $fixtures->get_variation_product(
			$product->get_id(),
			array( $taxonomy => $term_red->slug ),
			array(
				'regular_price' => 11,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$red_variation->set_image_id( $red_image_id );
		$red_variation->save();

		$red_second_variation = $fixtures->get_variation_product(
			$product->get_id(),
			array( $taxonomy => $term_red->slug ),
			array(
				'regular_price' => 12,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$red_second_variation->set_image_id( $red_second_id );
		$red_second_variation->save();

		$blue_variation = $fixtures->get_variation_product(
			$product->get_id(),
			array( $taxonomy => $term_blue->slug ),
			array(
				'regular_price' => 13,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$blue_variation->set_image_id( $blue_image_id );
		$blue_variation->save();

		\WC_Product_Variable::sync( $product->get_id() );

		$product = wc_get_product( $product->get_id() );
		$this->assertInstanceOf( \WC_Product_Variable::class, $product );

		return array(
			'product'         => $product,
			'attribute_id'    => (int) $attribute['attribute_id'],
			'term_ids'        => $attribute['term_ids'],
			'parent_image_id' => $parent_image_id,
			'red_image_id'    => $red_image_id,
			'red_second_id'   => $red_second_id,
			'blue_image_id'   => $blue_image_id,
		);
	}

	/**
	 * Render the block for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return string Rendered markup.
	 */
	private function render_block_for_product( int $product_id ): string {
		return do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} -->
				<!-- wp:woocommerce/product-image-with-color-swatches -->
					<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","showSaleBadge":false} /-->
					<!-- wp:woocommerce/product-filter-chips /-->
				<!-- /wp:woocommerce/product-image-with-color-swatches -->
			<!-- /wp:woocommerce/single-product -->'
		);
	}

	/**
	 * Create an image attachment that passes image checks.
	 *
	 * @param string $title         Attachment title.
	 * @param string $attached_file Synthetic file path.
	 * @return int Attachment ID.
	 */
	private function create_image_attachment( string $title, string $attached_file ): int {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		update_post_meta( $attachment_id, '_wp_attached_file', $attached_file );

		return $attachment_id;
	}

	/**
	 * Set product attribute type and clear WooCommerce attribute caches.
	 *
	 * @param int    $attribute_id Attribute ID.
	 * @param string $type         Attribute type.
	 */
	private function set_attribute_type( int $attribute_id, string $type ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_attribute_taxonomies',
			array( 'attribute_type' => $type ),
			array( 'attribute_id' => $attribute_id ),
			array( '%s' ),
			array( '%d' )
		);
		delete_transient( 'wc_attribute_taxonomies' );
		\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
	}

	/**
	 * Clean up variable product data.
	 *
	 * @param array<string, mixed> $data Test data.
	 */
	private function cleanup_variable_product_data( array $data ): void {
		$this->set_attribute_type( (int) $data['attribute_id'], 'select' );

		foreach ( $data['term_ids'] as $term_id ) {
			delete_term_meta( (int) $term_id, 'color' );
		}

		foreach ( array( 'parent_image_id', 'red_image_id', 'red_second_id', 'blue_image_id' ) as $key ) {
			wp_delete_attachment( (int) $data[ $key ], true );
		}

		if ( $data['product'] instanceof \WC_Product_Variable ) {
			$data['product']->delete( true );
		}
	}
}
