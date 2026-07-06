<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use WC_Unit_Test_Case;

/**
 * Tests for the is_standalone_line boolean added to CartItemSchema.
 *
 * Covers: get_item_response() returning is_standalone_line=true for plain standalone lines,
 * is_standalone_line=false for meta-differentiated lines, and get_properties()
 * returning a correctly-shaped is_standalone_line definition while still
 * inheriting all parent properties.
 */
class CartItemSchemaIsStandaloneLineTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CartItemSchema
	 */
	private $sut;

	/**
	 * A simple product used to build cart-item arrays.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );

		$extend            = new ExtendSchema( $formatters );
		$schema_controller = new SchemaController( $extend );
		$this->sut         = $schema_controller->get( CartItemSchema::IDENTIFIER );

		// Initialise the cart so generate_cart_id() is available.
		wc_empty_cart();

		$product_id = $this->factory->post->create(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => 'Test Product',
			)
		);
		update_post_meta( $product_id, '_price', '10' );
		update_post_meta( $product_id, '_regular_price', '10' );
		update_post_meta( $product_id, '_stock_status', 'instock' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		$this->product = wc_get_product( $product_id );
	}

	// -------------------------------------------------------------------------
	// get_item_response() — is_standalone_line field presence and value
	// -------------------------------------------------------------------------

	/**
	 * Builds a minimal but valid cart-item array for use in response tests.
	 *
	 * @param string $key           The cart item key.
	 * @param int    $product_id    The product ID.
	 * @param int    $variation_id  The variation ID (0 for simple products).
	 * @param array  $variation     Variation attributes.
	 * @return array
	 */
	private function build_cart_item( string $key, int $product_id, int $variation_id = 0, array $variation = array() ): array {
		return array(
			'key'               => $key,
			'data'              => $this->product,
			'product_id'        => $product_id,
			'variation_id'      => $variation_id,
			'variation'         => $variation,
			'quantity'          => 1,
			'line_subtotal'     => 10.00,
			'line_subtotal_tax' => 0.00,
			'line_total'        => 10.00,
			'line_tax'          => 0.00,
		);
	}

	/**
	 * @testdox Should include is_standalone_line=true in the response for a plain standalone line.
	 */
	public function test_get_item_response_returns_true_for_plain_line(): void {
		$product_id = $this->product->get_id();
		$plain_key  = WC()->cart->generate_cart_id( $product_id );
		$cart_item  = $this->build_cart_item( $plain_key, $product_id );

		$response = $this->sut->get_item_response( $cart_item );

		$this->assertArrayHasKey(
			'is_standalone_line',
			$response,
			'get_item_response() must include is_standalone_line for plain lines.'
		);
		$this->assertTrue(
			$response['is_standalone_line'],
			'A plain standalone line must have is_standalone_line=true.'
		);
	}

	/**
	 * @testdox Should include is_standalone_line=false in the response for a meta-differentiated line.
	 */
	public function test_get_item_response_returns_false_for_meta_differentiated_line(): void {
		$product_id     = $this->product->get_id();
		$cart_item_data = array( '_bundle' => 'bundle-parent-123' );
		$meta_key       = WC()->cart->generate_cart_id( $product_id, 0, array(), $cart_item_data );
		$cart_item      = $this->build_cart_item( $meta_key, $product_id );

		$response = $this->sut->get_item_response( $cart_item );

		$this->assertArrayHasKey(
			'is_standalone_line',
			$response,
			'get_item_response() must include is_standalone_line for meta-differentiated lines.'
		);
		$this->assertFalse(
			$response['is_standalone_line'],
			'A meta-differentiated line must have is_standalone_line=false.'
		);
	}

	// -------------------------------------------------------------------------
	// get_properties() — schema definition for is_standalone_line
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should define is_standalone_line as a readonly boolean with view and edit context in get_properties().
	 */
	public function test_get_properties_defines_is_standalone_line_as_readonly_boolean(): void {
		$properties = $this->sut->get_properties();

		$this->assertArrayHasKey(
			'is_standalone_line',
			$properties,
			'get_properties() must include is_standalone_line.'
		);

		$definition = $properties['is_standalone_line'];

		$this->assertSame(
			'boolean',
			$definition['type'],
			'is_standalone_line must be declared as type boolean.'
		);
		$this->assertSame(
			array( 'view', 'edit' ),
			$definition['context'],
			'is_standalone_line must have context [view, edit].'
		);
		$this->assertTrue(
			$definition['readonly'],
			'is_standalone_line must be readonly.'
		);
	}

	/**
	 * @testdox Should still return all parent-inherited properties in get_properties().
	 */
	public function test_get_properties_still_contains_inherited_properties(): void {
		$properties = $this->sut->get_properties();

		$inherited_keys = array( 'key', 'id', 'quantity', 'sold_individually' );

		foreach ( $inherited_keys as $inherited_key ) {
			$this->assertArrayHasKey(
				$inherited_key,
				$properties,
				"get_properties() must still return the inherited '{$inherited_key}' property."
			);
		}
	}
}
