<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\ProductVariationsClassicRedesign;

use Automattic\WooCommerce\Admin\Features\ProductVariationsClassicRedesign\Init;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductVariationsClassicRedesign Init class.
 */
class InitTest extends WC_Unit_Test_Case {
	/**
	 * @testdox is_product_edit_page returns false when get_current_screen returns null.
	 */
	public function test_is_product_edit_page_returns_false_with_no_screen() {
		// get_current_screen() returns null outside the admin context.
		$this->assertFalse( Init::is_product_edit_page() );
	}

	/**
	 * @testdox is_legacy_variation_edit returns false when edit_variation is absent.
	 */
	public function test_is_legacy_variation_edit_returns_false_when_absent() {
		unset( $_GET['edit_variation'] );
		$this->assertFalse( Init::is_legacy_variation_edit() );
	}

	/**
	 * @testdox is_legacy_variation_edit returns true when edit_variation is a numeric value.
	 */
	public function test_is_legacy_variation_edit_returns_true_with_numeric_variation_id() {
		$_GET['edit_variation'] = '123';
		$this->assertTrue( Init::is_legacy_variation_edit() );
		unset( $_GET['edit_variation'] );
	}

	/**
	 * @testdox is_legacy_variation_edit returns false when edit_variation is non-numeric.
	 */
	public function test_is_legacy_variation_edit_returns_false_with_non_numeric_value() {
		$_GET['edit_variation'] = 'invalid';
		$this->assertFalse( Init::is_legacy_variation_edit() );
		unset( $_GET['edit_variation'] );
	}
}
