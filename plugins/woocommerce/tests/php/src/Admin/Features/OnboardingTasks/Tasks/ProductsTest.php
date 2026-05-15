<?php
/**
 * Tests for the Products onboarding task filter hooks.
 *
 * @package WooCommerce\Admin\Tests\Admin\Features\OnboardingTasks\Tasks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Products;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Products onboarding task test.
 *
 * @class ProductsTest
 */
class ProductsTest extends WC_Unit_Test_Case {

	/**
	 * Clean up after each test case.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_transient( Products::HAS_PRODUCT_TRANSIENT );

		remove_all_filters( 'woocommerce_admin_onboarding_task_products_pre_has_products' );
		remove_all_filters( 'woocommerce_admin_onboarding_task_products_has_products' );
		remove_all_filters( 'woocommerce_admin_onboarding_task_products_is_valid_product' );

		parent::tearDown();
	}

	/**
	 * The pre-check filter short-circuits the default detection when it
	 * returns a non-null value.
	 *
	 * @return void
	 */
	public function test_pre_has_products_filter_short_circuits_to_false(): void {
		// Seed an auto-published product so the default detection would return true.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( 'publish' );
		$product->save();

		// Sanity check: without the filter, detection returns true.
		$this->assertTrue( Products::has_products() );

		delete_transient( Products::HAS_PRODUCT_TRANSIENT );

		add_filter(
			'woocommerce_admin_onboarding_task_products_pre_has_products',
			static function () {
				return false;
			}
		);

		$this->assertFalse( Products::has_products(), 'Pre filter should short-circuit detection to false.' );
	}

	/**
	 * The pre-check filter is bypassed when it returns null.
	 *
	 * @return void
	 */
	public function test_pre_has_products_filter_falls_back_when_null(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( 'publish' );
		$product->save();

		add_filter(
			'woocommerce_admin_onboarding_task_products_pre_has_products',
			static function () {
				return null;
			}
		);

		$this->assertTrue( Products::has_products(), 'Returning null should defer to the default detection.' );
	}

	/**
	 * The post-detection filter can flip the result to false even when
	 * the default detection finds products.
	 *
	 * @return void
	 */
	public function test_has_products_filter_can_override_default_result(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_status( 'publish' );
		$product->save();

		add_filter(
			'woocommerce_admin_onboarding_task_products_has_products',
			static function () {
				return false;
			}
		);

		$this->assertFalse( Products::has_products(), 'Post filter should be able to override the detection result.' );
	}
}
