<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Templates\SingleProduct;

use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the single product meta template.
 */
class SingleProductMetaTemplateTest extends WC_Unit_Test_Case {

	/**
	 * Whether the global product was set before the test.
	 *
	 * @var bool
	 */
	private bool $had_previous_product;

	/**
	 * The global product value from before the test.
	 *
	 * @var mixed
	 */
	private $previous_product;

	/**
	 * Product used by the test.
	 *
	 * @var \WC_Product
	 */
	private $test_product;

	/**
	 * Category names used by the test.
	 *
	 * @var array<string, string>
	 */
	private array $category_names = array();

	/**
	 * Category IDs used by the test.
	 *
	 * @var array<string, int>
	 */
	private array $category_ids = array();

	/**
	 * Set up the product category fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->had_previous_product = array_key_exists( 'product', $GLOBALS );
		$this->previous_product     = $GLOBALS['product'] ?? null;

		$suffix               = wp_unique_id();
		$this->category_names = array(
			'root' => 'Template Root ' . $suffix,
			'mid'  => 'Template Mid ' . $suffix,
			'leaf' => 'Template Leaf ' . $suffix,
		);
		$root                 = wp_insert_term( $this->category_names['root'], 'product_cat' );
		$mid                  = wp_insert_term( $this->category_names['mid'], 'product_cat', array( 'parent' => $root['term_id'] ) );
		$leaf                 = wp_insert_term( $this->category_names['leaf'], 'product_cat', array( 'parent' => $mid['term_id'] ) );
		$this->category_ids   = array(
			'root' => $root['term_id'],
			'mid'  => $mid['term_id'],
			'leaf' => $leaf['term_id'],
		);
		$this->test_product   = WC_Helper_Product::create_simple_product();

		$this->test_product->set_category_ids(
			array( $this->category_ids['leaf'], $this->category_ids['root'], $this->category_ids['mid'] ),
		);
		$this->test_product->save();
		$GLOBALS['product'] = $this->test_product;
	}

	/**
	 * Tear down the product category fixture.
	 */
	public function tearDown(): void {
		WC_Helper_Product::delete_product( $this->test_product->get_id() );

		foreach ( array_reverse( $this->category_ids ) as $category_id ) {
			wp_delete_term( $category_id, 'product_cat' );
		}

		if ( $this->had_previous_product ) {
			$GLOBALS['product'] = $this->previous_product;
		} else {
			unset( $GLOBALS['product'] );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Single product meta renders assigned categories in breadcrumb order.
	 */
	public function test_single_product_meta_renders_categories_in_breadcrumb_order(): void {
		$expected = implode(
			', ',
			array( $this->category_names['root'], $this->category_names['mid'], $this->category_names['leaf'] )
		);

		$this->assertStringContainsString(
			'Categories: ' . $expected,
			wp_strip_all_tags( wc_get_template_html( 'single-product/meta.php' ) ),
			'Single product meta should render product categories in root-to-leaf order.'
		);
	}

	/**
	 * @testdox Single product meta honors the filtered category ordering mode.
	 */
	public function test_single_product_meta_honors_filtered_category_order(): void {
		$orderby_filter = static function () {
			return 'name';
		};
		add_filter( 'woocommerce_product_meta_category_orderby', $orderby_filter );

		try {
			$expected = implode(
				', ',
				array( $this->category_names['leaf'], $this->category_names['mid'], $this->category_names['root'] )
			);

			$this->assertStringContainsString(
				'Categories: ' . $expected,
				wp_strip_all_tags( wc_get_template_html( 'single-product/meta.php' ) ),
				'Single product meta should honor the filtered product category ordering mode.'
			);
		} finally {
			remove_filter( 'woocommerce_product_meta_category_orderby', $orderby_filter );
		}
	}

	/**
	 * @testdox Single product meta omits category markup when category loading fails.
	 */
	public function test_single_product_meta_omits_categories_when_term_loading_fails(): void {
		$product_id   = $this->test_product->get_id();
		$terms_filter = static function ( $terms, $post_id, $taxonomy ) use ( $product_id ) {
			return $product_id === $post_id && 'product_cat' === $taxonomy ? new \WP_Error( 'category-list-error' ) : $terms;
		};
		add_filter( 'get_the_terms', $terms_filter, 10, 3 );

		try {
			$this->assertStringNotContainsString(
				'class="posted_in"',
				wc_get_template_html( 'single-product/meta.php' ),
				'Single product meta should suppress category markup when term loading fails.'
			);
		} finally {
			remove_filter( 'get_the_terms', $terms_filter, 10 );
		}
	}
}
