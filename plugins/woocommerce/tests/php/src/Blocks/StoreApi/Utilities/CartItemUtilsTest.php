<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\CartItemUtils;

/**
 * Tests for the CartItemUtils class.
 */
class CartItemUtilsTest extends \WC_Unit_Test_Case {

	/**
	 * A simple product ID used across tests.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Ensure the cart object is initialized so generate_cart_id() is callable.
		wc_empty_cart();
		$this->product_id = 42;
	}

	// -------------------------------------------------------------------------
	// Plain (standalone) line — is_standalone_line must return true
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return true for a plain simple-product line whose stored key matches the empty-data recompute.
	 */
	public function test_returns_true_for_plain_simple_product_line(): void {
		$plain_key = WC()->cart->generate_cart_id( $this->product_id );

		$cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$this->assertTrue(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A plain simple-product line should be reported as a standalone line.'
		);
	}

	/**
	 * @testdox Should return true for a plain variation line whose stored key matches the empty-data recompute.
	 */
	public function test_returns_true_for_plain_variation_line(): void {
		$variation_id = 7;
		$variation    = array( 'attribute_color' => 'red' );
		$plain_key    = WC()->cart->generate_cart_id( $this->product_id, $variation_id, $variation );

		$cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$this->assertTrue(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A plain variation line (no extra cart item data) should be reported as a standalone line.'
		);
	}

	// -------------------------------------------------------------------------
	// Meta-differentiated line — is_standalone_line must return false
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should return false for a simple-product line whose stored key was generated with non-empty cart_item_data.
	 */
	public function test_returns_false_for_simple_product_line_with_cart_item_data(): void {
		$cart_item_data = array( '_bundle' => 'bundle-parent-123' );
		$meta_key       = WC()->cart->generate_cart_id( $this->product_id, 0, array(), $cart_item_data );

		$cart_item = array(
			'key'          => $meta_key,
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$this->assertFalse(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A line whose key was generated with non-empty cart_item_data should not be reported as a standalone line.'
		);
	}

	/**
	 * @testdox Should return false for a variation line whose stored key was generated with non-empty cart_item_data.
	 */
	public function test_returns_false_for_variation_line_with_cart_item_data(): void {
		$variation_id   = 7;
		$variation      = array( 'attribute_color' => 'blue' );
		$cart_item_data = array( '_cart_line_identity' => 'composite-child-456' );
		$meta_key       = WC()->cart->generate_cart_id( $this->product_id, $variation_id, $variation, $cart_item_data );

		$cart_item = array(
			'key'          => $meta_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$this->assertFalse(
			CartItemUtils::is_standalone_line( $cart_item ),
			'A variation line whose key was generated with non-empty cart_item_data should not be reported as a standalone line.'
		);
	}

	/**
	 * @testdox Should return false for a variation line with cart_item_data even when variation attributes are the same.
	 */
	public function test_returns_false_for_variation_line_differentiates_from_plain_variation(): void {
		$variation_id = 7;
		$variation    = array( 'attribute_size' => 'large' );

		// The plain key has no cart_item_data.
		$plain_key = WC()->cart->generate_cart_id( $this->product_id, $variation_id, $variation );

		// The meta-differentiated key carries extra data.
		$meta_key = WC()->cart->generate_cart_id(
			$this->product_id,
			$variation_id,
			$variation,
			array( '_subscription_switch' => 'yes' )
		);

		// Sanity-check that the two keys are actually different.
		$this->assertNotSame( $plain_key, $meta_key );

		$plain_cart_item = array(
			'key'          => $plain_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$meta_cart_item = array(
			'key'          => $meta_key,
			'product_id'   => $this->product_id,
			'variation_id' => $variation_id,
			'variation'    => $variation,
		);

		$this->assertTrue(
			CartItemUtils::is_standalone_line( $plain_cart_item ),
			'The plain variation line must be reported as standalone.'
		);

		$this->assertFalse(
			CartItemUtils::is_standalone_line( $meta_cart_item ),
			'The meta-differentiated variation line must not be reported as a standalone line.'
		);
	}

	// -------------------------------------------------------------------------
	// Defensive / edge cases
	// -------------------------------------------------------------------------

	/**
	 * @testdox Should not fatal and should return false when WC()->cart is unavailable.
	 */
	public function test_returns_false_when_cart_is_unavailable(): void {
		// Temporarily detach the cart from the WC instance.
		$original_cart = WC()->cart;
		WC()->cart     = null; // phpcs:ignore

		$cart_item = array(
			'key'          => 'some-key',
			'product_id'   => $this->product_id,
			'variation_id' => 0,
			'variation'    => array(),
		);

		$result = CartItemUtils::is_standalone_line( $cart_item );

		// Restore cart immediately.
		WC()->cart = $original_cart; // phpcs:ignore

		$this->assertFalse(
			$result,
			'When WC()->cart is unavailable the helper must degrade gracefully and return false.'
		);
	}

	/**
	 * @testdox Should not fatal when cart item is missing product_id, variation_id, and variation keys.
	 */
	public function test_does_not_fatal_for_malformed_cart_item_missing_keys(): void {
		// With defaults (product_id=0, variation_id=0, variation=[]), the recomputed
		// key is generate_cart_id(0). Build a matching stored key so the result is
		// deterministic and we can assert on it while confirming no fatal occurs.
		$default_key = WC()->cart->generate_cart_id( 0 );

		$cart_item = array(
			'key' => $default_key,
			// product_id, variation_id, variation intentionally absent.
		);

		$result = CartItemUtils::is_standalone_line( $cart_item );

		$this->assertIsBool(
			$result,
			'The helper must return a bool and not fatal when product_id/variation_id/variation keys are absent.'
		);

		$this->assertTrue(
			$result,
			'When missing keys default to 0/[], the stored key matches the recomputed key, so the line is treated as standalone.'
		);
	}

	// -------------------------------------------------------------------------
	// Usage-example docblock prose
	// -------------------------------------------------------------------------

	/**
	 * @testdox The usage example must no longer show or suggest driving an Add-to-cart button (or any in-cart count) from the predicate.
	 */
	public function test_usage_example_does_not_suggest_driving_an_add_to_cart_button(): void {
		$doc_comment = $this->get_is_standalone_line_doc_comment();

		$this->assertStringNotContainsStringIgnoringCase(
			'add to cart',
			$doc_comment,
			'The docblock must not mention showing an "Add to cart" button from the predicate.'
		);
		$this->assertStringNotContainsStringIgnoringCase(
			'add-to-cart',
			$doc_comment,
			'The docblock must not mention driving an add-to-cart button from the predicate.'
		);
	}

	/**
	 * @testdox The rewritten prose must point count consumers at the filtered is_canonical_line field and name the filter.
	 */
	public function test_prose_points_count_consumers_at_filtered_is_canonical_line_field(): void {
		$doc_comment = $this->get_is_standalone_line_doc_comment();

		$this->assertStringContainsString(
			'is_canonical_line',
			$doc_comment,
			'The docblock must point count consumers at the Store API cart-item response\'s is_canonical_line field.'
		);
		$this->assertStringContainsString(
			'woocommerce_store_api_cart_item_is_canonical_line',
			$doc_comment,
			'The docblock must name the woocommerce_store_api_cart_item_is_canonical_line filter.'
		);
	}

	// -------------------------------------------------------------------------
	// Byte-identity of the retained method
	// -------------------------------------------------------------------------

	/**
	 * @testdox The method's name, signature and body must be byte-identical to the pre-existing implementation.
	 */
	public function test_method_name_signature_and_body_are_byte_identical(): void {
		$reflection = new \ReflectionMethod( CartItemUtils::class, 'is_standalone_line' );

		$this->assertSame( 'is_standalone_line', $reflection->getName() );
		$this->assertTrue( $reflection->isStatic(), 'is_standalone_line() must remain static.' );
		$this->assertTrue( $reflection->isPublic(), 'is_standalone_line() must remain public.' );
		$this->assertTrue( $reflection->hasReturnType(), 'is_standalone_line() must keep its declared return type.' );
		$this->assertSame( 'bool', (string) $reflection->getReturnType() );

		$parameters = $reflection->getParameters();
		$this->assertCount( 1, $parameters, 'is_standalone_line() must keep a single parameter.' );
		$this->assertSame( 'cart_item', $parameters[0]->getName() );
		$this->assertTrue( $parameters[0]->hasType() );
		$this->assertSame( 'array', (string) $parameters[0]->getType() );

		$expected_body = <<<'PHP'
	public static function is_standalone_line( array $cart_item ): bool {
		// @phpstan-ignore isset.property (WC()->cart is declared non-null but can be null before WC fully initialises; the guard is load-bearing for early-bootstrap callers)
		if ( ! isset( WC()->cart ) ) {
			return false;
		}

		$product_id   = (int) ( $cart_item['product_id'] ?? 0 );
		$variation_id = (int) ( $cart_item['variation_id'] ?? 0 );
		$variation    = is_array( $cart_item['variation'] ?? null ) ? $cart_item['variation'] : array();

		$standalone_key = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation );

		return ( $cart_item['key'] ?? '' ) === $standalone_key;
	}
PHP;

		$this->assertSame(
			$expected_body,
			$this->get_method_source( $reflection ),
			'is_standalone_line()\'s name, signature and body must be byte-identical to before.'
		);
	}

	/**
	 * Read a method's exact source text (signature through closing brace) from its declaring file.
	 *
	 * @param \ReflectionMethod $reflection The method to read.
	 * @return string
	 */
	private function get_method_source( \ReflectionMethod $reflection ): string {
		$filename = $reflection->getFileName();
		$this->assertIsString( $filename, 'The declaring file must be resolvable.' );

		$lines      = file( $filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local repository file, read only to assert on its contents in this test.
		$start_line = $reflection->getStartLine();
		$end_line   = $reflection->getEndLine();

		$body = array_slice( $lines, $start_line - 1, $end_line - $start_line + 1 );

		return rtrim( implode( '', $body ), "\n" );
	}

	/**
	 * Fetch the doc comment for CartItemUtils::is_standalone_line() via reflection.
	 *
	 * @return string
	 */
	private function get_is_standalone_line_doc_comment(): string {
		$reflection  = new \ReflectionMethod( CartItemUtils::class, 'is_standalone_line' );
		$doc_comment = $reflection->getDocComment();

		$this->assertIsString( $doc_comment, 'is_standalone_line() must have a docblock.' );

		return $doc_comment;
	}
}
