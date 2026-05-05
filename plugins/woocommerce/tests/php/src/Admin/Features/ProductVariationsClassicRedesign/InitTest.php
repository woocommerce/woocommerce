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
	 * Clean up global state after each test.
	 */
	public function tearDown(): void {
		unset( $_GET['edit_variation'] );
		wp_dequeue_script( Init::SCRIPT_HANDLE );
		wp_deregister_script( Init::SCRIPT_HANDLE );
		wp_dequeue_style( Init::SCRIPT_HANDLE );
		wp_deregister_style( Init::SCRIPT_HANDLE );
		set_current_screen();

		parent::tearDown();
	}

	/**
	 * Sets the current admin screen to the product edit page.
	 */
	private function set_product_edit_screen(): void {
		set_current_screen( 'post' );
		$screen            = get_current_screen();
		$screen->post_type = 'product';
		$screen->base      = 'post';
	}

	/**
	 * Registers the script and style handles used by the variation view.
	 */
	private function register_variation_view_assets(): void {
		wp_register_script( Init::SCRIPT_HANDLE, '', array(), '1.0.0', true );
		wp_register_style( Init::SCRIPT_HANDLE, false, array(), '1.0.0' );
	}

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

	/**
	 * @testdox enqueue_scripts loads the dedicated variation-view bundle and inline initializer.
	 */
	public function test_enqueue_scripts_loads_variation_view_bundle_and_inline_initializer() {
		$this->register_variation_view_assets();
		$this->set_product_edit_screen();

		global $post;
		$post = self::factory()->post->create_and_get(
			array(
				'post_type' => 'product',
			)
		);

		( new Init() )->enqueue_scripts();

		$this->assertTrue( wp_script_is( Init::SCRIPT_HANDLE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( Init::SCRIPT_HANDLE, 'enqueued' ) );

		$inline_scripts = wp_scripts()->get_data( Init::SCRIPT_HANDLE, 'after' );
		$this->assertIsArray( $inline_scripts );
		$inline_script = implode( "\n", $inline_scripts );

		$this->assertStringContainsString( 'window.wc.experimentalProductsAppVariationView.initializeVariationView', $inline_script );
		$this->assertStringContainsString( wp_json_encode( Init::ROOT_ID ), $inline_script );
		$this->assertStringContainsString( (string) $post->ID, $inline_script );
	}

	/**
	 * @testdox enqueue_scripts skips the bundle when editing a legacy variation.
	 */
	public function test_enqueue_scripts_skips_legacy_variation_edit() {
		$this->register_variation_view_assets();
		$this->set_product_edit_screen();
		$_GET['edit_variation'] = '123';

		( new Init() )->enqueue_scripts();

		$this->assertFalse( wp_script_is( Init::SCRIPT_HANDLE, 'enqueued' ) );
		$this->assertFalse( wp_style_is( Init::SCRIPT_HANDLE, 'enqueued' ) );
	}
}
