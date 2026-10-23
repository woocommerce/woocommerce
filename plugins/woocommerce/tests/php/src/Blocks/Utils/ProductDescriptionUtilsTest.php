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
	 * @testdox guarded_format() returns the formatted product description.
	 */
	public function test_guarded_format_returns_formatted_product_description(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_description( 'A formatted product description.' );
		$product->save();

		try {
			$nested_result = null;
			$result        = ProductDescriptionUtils::guarded_format(
				$product,
				function () use ( $product, &$nested_result ) {
					$nested_result = ProductDescriptionUtils::guarded_format(
						$product,
						function () use ( $product ) {
							return $product->get_description();
						}
					);

					return $product->get_description();
				}
			);

			$this->assertSame( '', $nested_result );
			$this->assertSame( 'A formatted product description.', $result );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
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
	 * @testdox guarded_format() does not invoke the formatter for a password-protected product.
	 */
	public function test_guarded_format_blocks_password_protected_product_description(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_post_password( 'secret' );
		$product->save();
		$formatter_called = false;

		$result = ProductDescriptionUtils::guarded_format(
			$product,
			function () use ( &$formatter_called ) {
				$formatter_called = true;

				return 'Protected description';
			}
		);

		$this->assertSame( '', $result, 'Protected product descriptions should not be formatted before password entry.' );
		$this->assertFalse( $formatter_called, 'The formatter should not receive the description before password entry.' );
	}

	/**
	 * @testdox guarded_format() protects variation descriptions using the parent product password.
	 */
	public function test_guarded_format_blocks_description_when_parent_is_password_protected(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_post_password( 'secret' );
		$product->save();
		$variation = wc_get_product( $product->get_children()[0] );

		$result = ProductDescriptionUtils::guarded_format(
			$variation,
			function () {
				return 'Protected variation description';
			}
		);

		$this->assertSame( '', $result, 'A variation description should not render until the parent product password is entered.' );
	}
}
