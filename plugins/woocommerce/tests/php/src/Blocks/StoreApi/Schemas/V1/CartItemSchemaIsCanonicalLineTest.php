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
 * Tests for the is_canonical_line boolean added to CartItemSchema.
 *
 * Covers: get_item_response() returning is_canonical_line=true for plain canonical lines,
 * is_canonical_line=false for meta-differentiated lines, the
 * woocommerce_store_api_cart_item_is_canonical_line filter overriding the computed value,
 * a non-boolean filter return being discarded in favor of the core-computed default, and
 * get_properties() returning a correctly-shaped is_canonical_line definition while still
 * inheriting all parent properties.
 */
class CartItemSchemaIsCanonicalLineTest extends WC_Unit_Test_Case {

	/**
	 * The name of the filter under test.
	 *
	 * @var string
	 */
	private const FILTER_HOOK = 'woocommerce_store_api_cart_item_is_canonical_line';

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

		remove_all_filters( self::FILTER_HOOK );

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

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( self::FILTER_HOOK );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// get_item_response() — is_canonical_line field presence and value
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
	 * @testdox Should include is_canonical_line=true in the response for a plain canonical line.
	 */
	public function test_get_item_response_returns_true_for_plain_line(): void {
		$product_id = $this->product->get_id();
		$plain_key  = WC()->cart->generate_cart_id( $product_id );
		$cart_item  = $this->build_cart_item( $plain_key, $product_id );

		$response = $this->sut->get_item_response( $cart_item );

		$this->assertArrayHasKey(
			'is_canonical_line',
			$response,
			'get_item_response() must include is_canonical_line for plain lines.'
		);
		$this->assertTrue(
			$response['is_canonical_line'],
			'A plain canonical line must have is_canonical_line=true.'
		);
		$this->assertArrayNotHasKey(
			'is_standalone_line',
			$response,
			'get_item_response() must not include the retired is_standalone_line key.'
		);
	}

	/**
	 * @testdox Should include is_canonical_line=false in the response for a meta-differentiated line.
	 */
	public function test_get_item_response_returns_false_for_meta_differentiated_line(): void {
		$product_id     = $this->product->get_id();
		$cart_item_data = array( '_bundle' => 'bundle-parent-123' );
		$meta_key       = WC()->cart->generate_cart_id( $product_id, 0, array(), $cart_item_data );
		$cart_item      = $this->build_cart_item( $meta_key, $product_id );

		$response = $this->sut->get_item_response( $cart_item );

		$this->assertArrayHasKey(
			'is_canonical_line',
			$response,
			'get_item_response() must include is_canonical_line for meta-differentiated lines.'
		);
		$this->assertFalse(
			$response['is_canonical_line'],
			'A meta-differentiated line must have is_canonical_line=false.'
		);
	}

	/**
	 * @testdox Should honor a filter that overrides a default-false line to true.
	 */
	public function test_get_item_response_honors_filter_overriding_default_false_to_true(): void {
		$product_id     = $this->product->get_id();
		$cart_item_data = array( '_bundle' => 'bundle-parent-123' );
		$meta_key       = WC()->cart->generate_cart_id( $product_id, 0, array(), $cart_item_data );
		$cart_item      = $this->build_cart_item( $meta_key, $product_id );

		add_filter(
			self::FILTER_HOOK,
			function () {
				return true;
			}
		);

		$response = $this->sut->get_item_response( $cart_item );

		$this->assertTrue(
			$response['is_canonical_line'],
			'A filter returning true must override a default-false line.'
		);
	}

	/**
	 * @testdox Should honor a filter that overrides a default-true line to false.
	 */
	public function test_get_item_response_honors_filter_overriding_default_true_to_false(): void {
		$product_id = $this->product->get_id();
		$plain_key  = WC()->cart->generate_cart_id( $product_id );
		$cart_item  = $this->build_cart_item( $plain_key, $product_id );

		add_filter(
			self::FILTER_HOOK,
			function () {
				return false;
			}
		);

		$response = $this->sut->get_item_response( $cart_item );

		$this->assertFalse(
			$response['is_canonical_line'],
			'A filter returning false must override a default-true line.'
		);
	}

	/**
	 * @testdox Should discard a non-boolean filter return in favor of the core-computed default.
	 * @dataProvider provider_non_boolean_filter_returns
	 *
	 * @param mixed $non_boolean_value The non-boolean value returned by the filter callback.
	 */
	public function test_get_item_response_discards_non_boolean_filter_return( $non_boolean_value ): void {
		$product_id = $this->product->get_id();
		$plain_key  = WC()->cart->generate_cart_id( $product_id );
		$cart_item  = $this->build_cart_item( $plain_key, $product_id );

		add_filter(
			self::FILTER_HOOK,
			function () use ( $non_boolean_value ) {
				return $non_boolean_value;
			}
		);

		$response = $this->sut->get_item_response( $cart_item );

		$this->assertTrue(
			is_bool( $response['is_canonical_line'] ),
			'A non-boolean filter return must be discarded in favor of a genuine boolean default.'
		);
		$this->assertTrue(
			$response['is_canonical_line'],
			'A non-boolean filter return must not override the core-computed default (true for a plain line).'
		);
	}

	/**
	 * Data provider of non-boolean values a filter callback might return.
	 *
	 * @return array
	 */
	public function provider_non_boolean_filter_returns(): array {
		return array(
			'int 0'        => array( 0 ),
			'empty string' => array( '' ),
			'null'         => array( null ),
			'string false' => array( 'false' ),
		);
	}

	// -------------------------------------------------------------------------
	// get_properties() — schema definition for is_canonical_line
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should define is_canonical_line as a readonly boolean with view and edit context in get_properties().
	 */
	public function test_get_properties_defines_is_canonical_line_as_readonly_boolean(): void {
		$properties = $this->sut->get_properties();

		$this->assertArrayHasKey(
			'is_canonical_line',
			$properties,
			'get_properties() must include is_canonical_line.'
		);

		$definition = $properties['is_canonical_line'];

		$this->assertSame(
			'boolean',
			$definition['type'],
			'is_canonical_line must be declared as type boolean.'
		);
		$this->assertSame(
			array( 'view', 'edit' ),
			$definition['context'],
			'is_canonical_line must have context [view, edit].'
		);
		$this->assertTrue(
			$definition['readonly'],
			'is_canonical_line must be readonly.'
		);
		$this->assertNotEmpty(
			$definition['description'],
			'is_canonical_line must have a description.'
		);

		$this->assertArrayNotHasKey(
			'is_standalone_line',
			$properties,
			'get_properties() must not expose the retired is_standalone_line property.'
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
