<?php
/**
 * Tests that product titles are passed through the `the_title` filter in the
 * cart and grouped product templates.
 *
 * @package WooCommerce\Tests\Templates
 */

/**
 * Class WC_Template_The_Title_Filter_Test
 */
class WC_Template_The_Title_Filter_Test extends \WC_Unit_Test_Case {

	/**
	 * Marker appended by the test `the_title` filter so the assertion can detect
	 * that the filter actually ran for the product title.
	 */
	private const TITLE_MARKER = 'QA_THE_TITLE_RAN';

	/**
	 * Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'the_title', array( $this, 'append_marker_to_title' ), 10, 2 );
	}

	/**
	 * Called after every test.
	 */
	public function tearDown(): void {
		remove_filter( 'the_title', array( $this, 'append_marker_to_title' ), 10 );

		WC()->cart->empty_cart();

		parent::tearDown();
	}

	/**
	 * Appends a marker to the title so tests can detect whether `the_title`
	 * actually ran on a product's name.
	 *
	 * @param string $title Title being filtered.
	 * @param int    $id    Post ID, if provided.
	 * @return string
	 */
	public function append_marker_to_title( $title, $id = 0 ) {
		return $title . self::TITLE_MARKER;
	}

	/**
	 * @testdox Cart template should pass product titles through the `the_title` filter.
	 */
	public function test_cart_template_applies_the_title_filter_to_product_name(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( "L'Eglise" );
		$product->save();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		ob_start();
		wc_get_template( 'cart/cart.php' );
		$html = ob_get_clean();

		$this->assertStringContainsString(
			"L'Eglise" . self::TITLE_MARKER,
			$html,
			'Cart template should run the product name through the `the_title` filter.'
		);
	}

	/**
	 * @testdox Grouped product template should pass child product titles through the `the_title` filter.
	 */
	public function test_grouped_template_applies_the_title_filter_to_child_product_name(): void {
		$child = WC_Helper_Product::create_simple_product();
		$child->set_name( "L'Eglise" );
		$child->save();

		$grouped = new WC_Product_Grouped();
		$grouped->set_name( 'Groupe Test' );
		$grouped->set_status( 'publish' );
		$grouped->set_children( array( $child->get_id() ) );
		$grouped->save();

		// The grouped template expects globals + the $grouped_products array.
		global $product, $post;
		$previous_product = $product;
		$previous_post    = $post;

		$product           = $grouped; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post              = get_post( $grouped->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$grouped_products  = array_filter( array_map( 'wc_get_product', $grouped->get_children() ) );

		ob_start();
		wc_get_template(
			'single-product/add-to-cart/grouped.php',
			array(
				'grouped_products' => $grouped_products,
			)
		);
		$html = ob_get_clean();

		$product = $previous_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post    = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->assertStringContainsString(
			"L'Eglise" . self::TITLE_MARKER,
			$html,
			'Grouped product template should run child product names through the `the_title` filter.'
		);
	}
}
