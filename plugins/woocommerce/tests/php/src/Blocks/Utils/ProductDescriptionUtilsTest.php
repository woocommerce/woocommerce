<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductDescription;
use Automattic\WooCommerce\Blocks\Utils\ProductDescriptionUtils;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ImageAttachmentSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;
use WC_Helper_Product;

/**
 * Tests for ProductDescriptionUtils.
 */
class ProductDescriptionUtilsTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox guarded_format() returns an empty string for same-product recursive formatting.
	 */
	public function test_guarded_format_returns_empty_string_for_recursive_same_product_formatting(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$result = ProductDescriptionUtils::guarded_format(
				$product,
				function () use ( $product ) {
					$recursive_result = ProductDescriptionUtils::guarded_format(
						$product,
						function () {
							return 'Recursive description';
						}
					);

					return 'Outer description: ' . $recursive_result;
				}
			);

			$this->assertSame( 'Outer description: ', $result );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox ProductSchema shares the active ProductDescription recursion guard for the same product.
	 */
	public function test_product_schema_uses_same_guard_as_product_description_block(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_short_description( 'Short product description.' );
		$product->set_description( 'Outer product description.' );
		$product->save();

		$schema                       = $this->create_product_schema();
		$schema_response              = null;
		$schema_calls                 = 0;
		$formatted_content_raw_values = array();

		$format_content_callback = function ( $content, $raw_content ) use ( &$formatted_content_raw_values ) {
			$formatted_content_raw_values[] = $raw_content;
			return $content;
		};

		$the_content_callback = function ( $content ) use ( $schema, $product, &$schema_response, &$schema_calls ) {
			++$schema_calls;
			$schema_response = $schema->get_item_response( $product );

			return $content;
		};

		add_filter( 'woocommerce_format_content', $format_content_callback, 10, 2 );
		add_filter( 'the_content', $the_content_callback );

		try {
			$rendered_block = $this->render_product_description_block( $product );

			$this->assertSame( 1, $schema_calls );
			$this->assertSame( array( 'Short product description.' ), $formatted_content_raw_values );
			$this->assertIsArray( $schema_response );
			$this->assertSame( '', $schema_response['description'] );
			$this->assertStringContainsString( 'Outer product description.', $rendered_block );
		} finally {
			remove_filter( 'woocommerce_format_content', $format_content_callback );
			remove_filter( 'the_content', $the_content_callback );
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox guarded_format() allows nested formatting for a different product.
	 */
	public function test_guarded_format_allows_nested_different_product_formatting(): void {
		$product       = WC_Helper_Product::create_simple_product();
		$other_product = WC_Helper_Product::create_simple_product();
		$other_product->set_name( 'Nested Product' );
		$other_product->save();

		try {
			$result = ProductDescriptionUtils::guarded_format(
				$product,
				function () use ( $other_product ) {
					$nested_result = ProductDescriptionUtils::guarded_format(
						$other_product,
						function () use ( $other_product ) {
							return $other_product->get_name();
						}
					);

					return 'Outer description: ' . $nested_result;
				}
			);

			$this->assertSame( 'Outer description: Nested Product', $result );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
			WC_Helper_Product::delete_product( $other_product->get_id() );
		}
	}

	/**
	 * @testdox guarded_format() returns an empty string for non-string formatted values.
	 */
	public function test_guarded_format_returns_empty_string_for_non_string_result(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$result = ProductDescriptionUtils::guarded_format(
				$product,
				function () {
					return array( 'not a string' );
				}
			);

			$this->assertSame( '', $result );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox guarded_format() clears the recursion guard when formatting throws.
	 */
	public function test_guarded_format_clears_guard_when_callback_throws(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			try {
				ProductDescriptionUtils::guarded_format(
					$product,
					function () {
						throw new \Exception( 'Formatting failed.' );
					}
				);
			} catch ( \Exception $exception ) {
				$this->assertSame( 'Formatting failed.', $exception->getMessage() );
			}

			$result = ProductDescriptionUtils::guarded_format(
				$product,
				function () {
					return 'Description after failure';
				}
			);

			$this->assertSame( 'Description after failure', $result );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * Create a ProductSchema instance for testing product response formatting.
	 *
	 * @return ProductSchema
	 */
	private function create_product_schema(): ProductSchema {
		$formatters   = new Formatters();
		$extend       = new ExtendSchema( $formatters );
		$controller   = $this->createMock( SchemaController::class );
		$image_schema = $this->createMock( ImageAttachmentSchema::class );

		$controller
			->method( 'get' )
			->with( ImageAttachmentSchema::IDENTIFIER )
			->willReturn( $image_schema );

		return new ProductSchema( $extend, $controller );
	}

	/**
	 * Render the Product Description block for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string Rendered block output.
	 */
	private function render_product_description_block( \WC_Product $product ): string {
		$reflection = new \ReflectionClass( ProductDescription::class );
		$block_type = $reflection->newInstanceWithoutConstructor();

		$render_method = $reflection->getMethod( 'render' );
		$render_method->setAccessible( true );

		$block = (object) array(
			'context' => array(
				'postId' => $product->get_id(),
			),
		);

		$previous_block_to_render            = \WP_Block_Supports::$block_to_render;
		\WP_Block_Supports::$block_to_render = array(
			'blockName' => 'woocommerce/product-description',
			'attrs'     => array(),
		);

		try {
			return (string) $render_method->invoke( $block_type, array(), '', $block );
		} finally {
			\WP_Block_Supports::$block_to_render = $previous_block_to_render;
		}
	}
}
