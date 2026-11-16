<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Collection;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product_Collection email block renderer: which products an email shows, and how.
 */
class ProductCollectionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Product_Collection
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Product_Collection();
	}

	/**
	 * @testdox Shows hand-picked products in the order they were picked, without excluded ones.
	 */
	public function test_hand_picked_products_keep_their_order(): void {
		$first  = $this->create_product( 'Picked first' );
		$second = $this->create_product( 'Picked second' );
		$third  = $this->create_product( 'Picked excluded' );

		$names = $this->rendered_names(
			array(
				'woocommerceHandPickedProducts' => array( $second->get_id(), $first->get_id(), $third->get_id() ),
				'exclude'                       => array( $third->get_id() ),
			)
		);

		$this->assertSame( array( 'Picked second', 'Picked first' ), $names );
	}

	/**
	 * @testdox Shows only featured products in the featured collection.
	 */
	public function test_featured_collection_shows_featured_products(): void {
		$this->create_product( 'Featured', array( 'featured' => true ) );
		$this->create_product( 'Plain' );

		$this->assertSame( array( 'Featured' ), $this->rendered_names( array(), 'woocommerce/product-collection/featured' ) );
	}

	/**
	 * @testdox Shows products on sale in the on-sale collection, including variable products, but not ended sales.
	 */
	public function test_on_sale_collection_follows_current_sales(): void {
		$this->create_product( 'Simple on sale', array( 'sale_price' => 5 ) );
		$this->create_product( 'Full price' );
		$this->create_product(
			'Sale ended',
			array(
				'sale_price'        => 5,
				'date_on_sale_from' => '-10 days',
				'date_on_sale_to'   => '-2 days',
			)
		);
		$variable = WC_Helper_Product::create_variation_product();
		$variable->set_name( 'Variable on sale' );
		$variable->save();
		$variation = wc_get_product( $variable->get_children()[0] );
		$variation->set_sale_price( '1' );
		$variation->save();
		\WC_Product_Variable::sync( $variable->get_id() );
		delete_transient( 'wc_products_onsale' );

		$names = $this->rendered_names( array(), 'woocommerce/product-collection/on-sale' );
		sort( $names );

		$this->assertSame( array( 'Simple on sale', 'Variable on sale' ), $names );
	}

	/**
	 * @testdox Leaves out products hidden from the shop catalog.
	 */
	public function test_hides_products_hidden_from_catalog(): void {
		$this->create_product( 'Visible' );
		$hidden = $this->create_product( 'Hidden', array( 'catalog_visibility' => 'hidden' ) );

		$this->assertSame( array( 'Visible' ), $this->rendered_names( array() ) );
		$this->assertSame( array(), $this->rendered_names( array( 'woocommerceHandPickedProducts' => array( $hidden->get_id() ) ) ), 'Hand-picked' );
	}

	/**
	 * @testdox Filters by stock status only when not every status is selected.
	 */
	public function test_stock_status_filter(): void {
		$this->create_product( 'In stock' );
		$this->create_product( 'Sold out', array( 'stock_status' => 'outofstock' ) );

		$this->assertSame( array( 'In stock' ), $this->rendered_names( array( 'woocommerceStockStatus' => array( 'instock' ) ) ), 'Only in stock' );

		$all   = array_keys( wc_get_product_stock_status_options() );
		$names = $this->rendered_names( array( 'woocommerceStockStatus' => $all ) );
		sort( $names );
		$this->assertSame( array( 'In stock', 'Sold out' ), $names, 'All statuses' );
	}

	/**
	 * @testdox Shows the upsells and cross-sells of the referenced product, without the product itself.
	 */
	public function test_upsells_and_cross_sells_of_referenced_product(): void {
		$upsell     = $this->create_product( 'Upsell' );
		$cross_sell = $this->create_product( 'Cross-sell' );
		$main       = $this->create_product(
			'Main',
			array(
				'upsell_ids'     => array( $upsell->get_id() ),
				'cross_sell_ids' => array( $cross_sell->get_id() ),
			)
		);
		$query      = array( 'productReference' => $main->get_id() );

		$this->assertSame( array( 'Upsell' ), $this->rendered_names( $query, 'woocommerce/product-collection/upsells' ) );
		$this->assertSame( array( 'Cross-sell' ), $this->rendered_names( $query, 'woocommerce/product-collection/cross-sells' ) );
	}

	/**
	 * @testdox Shows no products in a related collection without a product reference.
	 * @testWith ["woocommerce/product-collection/upsells"]
	 *           ["woocommerce/product-collection/cross-sells"]
	 *           ["woocommerce/product-collection/related"]
	 *
	 * @param string $collection Collection type.
	 */
	public function test_related_collections_without_reference_show_nothing( string $collection ): void {
		$this->create_product( 'Any product' );

		$html = $this->render( array(), $collection );

		$this->assertSame( array(), $this->names_in( $html ) );
		$this->assertStringContainsString( 'No products found.', $html );
	}

	/**
	 * @testdox Shows the products in the recipient's saved cart.
	 */
	public function test_cart_contents_for_customer_with_cart(): void {
		$in_cart = $this->create_product( 'In cart' );
		$this->create_product( 'Not in cart' );
		$user_id = $this->factory->user->create( array( 'user_email' => 'shopper@example.com' ) );
		update_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), array( 'cart' => array( 'key' => array( 'product_id' => $in_cart->get_id() ) ) ) );

		$names = $this->rendered_names( array(), 'woocommerce/product-collection/cart-contents', array( 'recipient_email' => 'shopper@example.com' ) );

		$this->assertSame( array( 'In cart' ), $names );
	}

	/**
	 * @testdox Shows no products for a recipient without a cart, and sample products in a preview.
	 */
	public function test_cart_contents_without_cart(): void {
		$this->create_product( 'Sample' );

		$this->assertSame( array(), $this->rendered_names( array(), 'woocommerce/product-collection/cart-contents', array( 'recipient_email' => 'guest@example.com' ) ), 'Sent email' );
		$this->assertSame( array( 'Sample' ), $this->rendered_names( array(), 'woocommerce/product-collection/cart-contents', array( 'is_user_preview' => true ) ), 'Preview' );
	}

	/**
	 * @testdox Keeps the on-sale filter on an upsells collection.
	 */
	public function test_on_sale_filter_applies_to_upsells(): void {
		$sale = $this->create_product( 'Upsell on sale', array( 'sale_price' => 5 ) );
		$full = $this->create_product( 'Upsell full price' );
		$main = $this->create_product( 'Main', array( 'upsell_ids' => array( $sale->get_id(), $full->get_id() ) ) );
		delete_transient( 'wc_products_onsale' );

		$names = $this->rendered_names(
			array(
				'productReference'  => $main->get_id(),
				'woocommerceOnSale' => true,
			),
			'woocommerce/product-collection/upsells'
		);

		$this->assertSame( array( 'Upsell on sale' ), $names );
	}

	/**
	 * @testdox Shows every product in the recipient's cart, also ones hidden from the catalog, as the shop's cart does.
	 */
	public function test_cart_contents_include_catalog_hidden_products(): void {
		$visible = $this->create_product( 'Visible in cart' );
		$hidden  = $this->create_product( 'Search-only in cart', array( 'catalog_visibility' => 'search' ) );
		$user_id = $this->factory->user->create( array( 'user_email' => 'shopper@example.com' ) );
		update_user_meta(
			$user_id,
			'_woocommerce_persistent_cart_' . get_current_blog_id(),
			array(
				'cart' => array(
					'a' => array( 'product_id' => $visible->get_id() ),
					'b' => array( 'product_id' => $hidden->get_id() ),
				),
			)
		);

		$names = $this->rendered_names( array(), 'woocommerce/product-collection/cart-contents', array( 'recipient_email' => 'shopper@example.com' ) );
		sort( $names );

		$this->assertSame( array( 'Search-only in cart', 'Visible in cart' ), $names );
	}

	/**
	 * @testdox Filters by category, in both forms the block stores, and by attribute.
	 */
	public function test_category_and_attribute_filters(): void {
		$category = wp_insert_term( 'Shirts', 'product_cat' );
		$shirt    = $this->create_product( 'Shirt', array( 'category_ids' => array( $category['term_id'] ) ) );
		$this->create_product( 'Mug' );
		$attribute = WC_Helper_Product::create_attribute( 'colour', array( 'red', 'blue' ) );
		wp_set_object_terms( $shirt->get_id(), array( 'red' ), $attribute['attribute_taxonomy'] );

		$this->assertSame( array( 'Shirt' ), $this->rendered_names( array( 'taxQuery' => array( 'product_cat' => array( $category['term_id'] ) ) ) ), 'Taxonomy map' );
		$this->assertSame(
			array( 'Shirt' ),
			$this->rendered_names(
				array(
					'taxQuery' => array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => array( $category['term_id'] ),
						),
					),
				)
			),
			'Clause list'
		);
		$this->assertSame(
			array( 'Shirt' ),
			$this->rendered_names(
				array(
					'woocommerceAttributes' => array(
						array(
							'taxonomy' => $attribute['attribute_taxonomy'],
							'termId'   => $attribute['term_ids'][0],
						),
					),
				)
			),
			'Attribute'
		);
	}

	/**
	 * @testdox Skips the number of products set as the offset.
	 */
	public function test_offset_skips_products(): void {
		$ids = array();
		foreach ( array( 'First', 'Second', 'Third' ) as $name ) {
			$ids[] = $this->create_product( $name )->get_id();
		}

		$names = $this->rendered_names(
			array(
				'woocommerceHandPickedProducts' => $ids,
				'offset'                        => 1,
			)
		);

		$this->assertSame( array( 'Second', 'Third' ), $names );
	}

	/**
	 * @testdox Fills the last row with an empty cell in a two-column grid with an odd product count.
	 */
	public function test_two_columns_with_odd_product_count(): void {
		$ids = array();
		foreach ( array( 'One', 'Two', 'Three' ) as $name ) {
			$ids[] = $this->create_product( $name )->get_id();
		}

		$html = $this->render( array( 'woocommerceHandPickedProducts' => $ids ), '', array(), 2 );

		$this->assertSame( array( 'One', 'Two', 'Three' ), $this->names_in( $html ) );
		$this->assertSame( 2, substr_count( $html, '<tr><td style="width: 50%' ), 'Two product rows' );
		$this->assertStringContainsString( 'padding-left: 10px;"></td></tr>', $html, 'Empty cell after the third product' );
	}

	/**
	 * Renders a collection and returns the product names it shows, in order.
	 *
	 * @param array  $query         Collection query attributes.
	 * @param string $collection    Collection type.
	 * @param array  $email_context Email context.
	 */
	private function rendered_names( array $query, string $collection = '', array $email_context = array() ): array {
		return $this->names_in( $this->render( $query, $collection, $email_context ) );
	}

	/**
	 * Renders a collection whose product template shows each product's title.
	 *
	 * @param array  $query         Collection query attributes.
	 * @param string $collection    Collection type.
	 * @param array  $email_context Email context.
	 * @param int    $columns       Number of columns.
	 */
	private function render( array $query, string $collection = '', array $email_context = array(), int $columns = 1 ): string {
		$title        = array(
			'blockName'    => 'core/post-title',
			'attrs'        => array(),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
		$parsed_block = array(
			'blockName'   => 'woocommerce/product-collection',
			'attrs'       => array(
				'collection'    => $collection,
				'query'         => $query,
				'displayLayout' => array( 'columns' => $columns ),
			),
			'innerBlocks' => array(
				array(
					'blockName'   => 'woocommerce/product-template',
					'attrs'       => array(),
					'innerBlocks' => array( $title ),
				),
			),
		);
		return $this->sut->render( '', $parsed_block, new Rendering_Context( new \WP_Theme_JSON(), $email_context ) );
	}

	/**
	 * Product names created by this test that appear in the HTML, in order.
	 *
	 * @param string $html Rendered HTML.
	 */
	private function names_in( string $html ): array {
		$document        = new \DOMDocument();
		$previous_errors = libxml_use_internal_errors( true );
		try {
			$document->loadHTML( '<?xml encoding="utf-8"?>' . $html );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_errors );
		}
		$names = array();
		foreach ( ( new \DOMXPath( $document ) )->query( '//h1|//h2|//h3|//h4|//h5|//h6' ) as $heading ) {
			$names[] = trim( html_entity_decode( wp_strip_all_tags( (string) $document->saveHTML( $heading ) ) ) );
		}
		return $names;
	}

	/**
	 * Creates a published simple product.
	 *
	 * @param string $name  Product name.
	 * @param array  $props Extra product properties.
	 */
	private function create_product( string $name, array $props = array() ): \WC_Product {
		return WC_Helper_Product::create_simple_product( true, array_merge( array( 'name' => $name ), $props ) );
	}
}
