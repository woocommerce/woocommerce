<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\ProductDescriptionUtils;
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
}
