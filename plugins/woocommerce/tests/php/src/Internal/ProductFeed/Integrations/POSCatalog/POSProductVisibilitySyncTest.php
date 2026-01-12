<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFeed\Integrations\POSCatalog;

use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\POSProductVisibilitySync;
use WC_Helper_Product;

/**
 * POSProductVisibilitySync test class.
 */
class POSProductVisibilitySyncTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var POSProductVisibilitySync
	 */
	private POSProductVisibilitySync $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new POSProductVisibilitySync();

		// Ensure the taxonomy is registered for tests.
		if ( ! taxonomy_exists( 'pos_product_visibility' ) ) {
			register_taxonomy( 'pos_product_visibility', 'product' );
		}

		// Ensure the pos-hidden term exists.
		if ( ! term_exists( 'pos-hidden', 'pos_product_visibility' ) ) {
			wp_insert_term( 'pos-hidden', 'pos_product_visibility' );
		}
	}

	/**
	 * Test that a simple product gets pos-hidden term when set to hidden.
	 */
	public function test_set_product_pos_visibility_simple_product_hidden(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->sut->set_product_pos_visibility( $product->get_id(), false );

		$this->assertTrue( has_term( 'pos-hidden', 'pos_product_visibility', $product->get_id() ) );
	}

	/**
	 * Test that a simple product has pos-hidden term removed when set to visible.
	 */
	public function test_set_product_pos_visibility_simple_product_visible(): void {
		$product = WC_Helper_Product::create_simple_product();

		// First set as hidden.
		wp_set_object_terms( $product->get_id(), 'pos-hidden', 'pos_product_visibility' );
		$this->assertTrue( has_term( 'pos-hidden', 'pos_product_visibility', $product->get_id() ) );

		// Then set as visible.
		$this->sut->set_product_pos_visibility( $product->get_id(), true );

		$this->assertFalse( has_term( 'pos-hidden', 'pos_product_visibility', $product->get_id() ) );
	}

	/**
	 * Test that a variable product and all its variations get pos-hidden term when set to hidden.
	 */
	public function test_set_product_pos_visibility_variable_product_hidden(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();

		$this->sut->set_product_pos_visibility( $product->get_id(), false );

		$this->assertTrue( has_term( 'pos-hidden', 'pos_product_visibility', $product->get_id() ) );
		foreach ( $variation_ids as $variation_id ) {
			$this->assertTrue(
				has_term( 'pos-hidden', 'pos_product_visibility', $variation_id ),
				"Variation $variation_id should have pos-hidden term"
			);
		}
	}

	/**
	 * Test that a variable product and all its variations have pos-hidden term removed when set to visible.
	 */
	public function test_set_product_pos_visibility_variable_product_visible(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();

		// First set as hidden.
		wp_set_object_terms( $product->get_id(), 'pos-hidden', 'pos_product_visibility' );
		foreach ( $variation_ids as $variation_id ) {
			wp_set_object_terms( $variation_id, 'pos-hidden', 'pos_product_visibility' );
		}

		// Then set as visible.
		$this->sut->set_product_pos_visibility( $product->get_id(), true );

		$this->assertFalse( has_term( 'pos-hidden', 'pos_product_visibility', $product->get_id() ) );
		foreach ( $variation_ids as $variation_id ) {
			$this->assertFalse(
				has_term( 'pos-hidden', 'pos_product_visibility', $variation_id ),
				"Variation $variation_id should not have pos-hidden term"
			);
		}
	}

	/**
	 * Test that a new variation inherits pos-hidden term from parent.
	 */
	public function test_inherit_parent_pos_visibility_parent_hidden(): void {
		$product = WC_Helper_Product::create_variation_product();

		// Set parent as hidden.
		wp_set_object_terms( $product->get_id(), 'pos-hidden', 'pos_product_visibility' );

		// Create a new variation.
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->save();

		$this->sut->inherit_parent_pos_visibility( $variation->get_id(), $variation );

		$this->assertTrue( has_term( 'pos-hidden', 'pos_product_visibility', $variation->get_id() ) );
	}

	/**
	 * Test that a new variation does not inherit pos-hidden term when parent is visible.
	 */
	public function test_inherit_parent_pos_visibility_parent_visible(): void {
		$product = WC_Helper_Product::create_variation_product();

		// Ensure parent does not have pos-hidden term.
		wp_remove_object_terms( $product->get_id(), 'pos-hidden', 'pos_product_visibility' );

		// Create a new variation.
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->save();

		$this->sut->inherit_parent_pos_visibility( $variation->get_id(), $variation );

		$this->assertFalse( has_term( 'pos-hidden', 'pos_product_visibility', $variation->get_id() ) );
	}

	/**
	 * Test that inherit_parent_pos_visibility handles invalid variation gracefully.
	 */
	public function test_inherit_parent_pos_visibility_invalid_variation(): void {
		// Pass a non-WC_Product_Variation object.
		$this->sut->inherit_parent_pos_visibility( 123, null );

		// No exception should be thrown - test passes if we reach this point.
		$this->assertTrue( true );
	}
}
