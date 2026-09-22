<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutLink;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;

/**
 * Unit tests for CheckoutLink.
 */
class CheckoutLinkTest extends \WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var CheckoutLink
	 */
	private $sut;

	/**
	 * Product IDs created by a test.
	 *
	 * @var int[]
	 */
	private $product_ids = array();

	/**
	 * Coupon IDs created by a test.
	 *
	 * @var int[]
	 */
	private $coupon_ids = array();

	/**
	 * Original request globals.
	 *
	 * @var array<string, mixed>
	 */
	private $original_get;

	/**
	 * Original cookies.
	 *
	 * @var array<string, mixed>
	 */
	private $original_cookie;

	/**
	 * Original query string.
	 *
	 * @var string|null
	 */
	private $original_query_string;

	/**
	 * Whether the original query string existed.
	 *
	 * @var bool
	 */
	private $had_query_string;

	/**
	 * Original current user ID.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Original WooCommerce session.
	 *
	 * @var \WC_Session
	 */
	private $original_session;

	/**
	 * Original WooCommerce cart.
	 *
	 * @var \WC_Cart
	 */
	private $original_cart;

	/**
	 * Filter callback that provides a deterministic Cart URL.
	 *
	 * @var \Closure
	 */
	private $cart_url_filter;

	/**
	 * Filter callback that provides a deterministic Checkout URL.
	 *
	 * @var \Closure
	 */
	private $checkout_url_filter;

	/**
	 * Set up an isolated checkout-link runtime.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_get          = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_cookie       = $_COOKIE;
		$this->had_query_string      = array_key_exists( 'QUERY_STRING', $_SERVER );
		$this->original_query_string = $this->had_query_string ? (string) $_SERVER['QUERY_STRING'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$this->original_user_id      = get_current_user_id();
		$this->original_session      = WC()->session;
		$this->original_cart         = WC()->cart;
		$this->sut                   = new CheckoutLink();
		$this->cart_url_filter       = static function () {
			return 'https://example.org/test-cart/';
		};
		$this->checkout_url_filter   = static function () {
			return 'https://example.org/test-checkout/';
		};

		add_filter( 'woocommerce_get_cart_url', $this->cart_url_filter );
		add_filter( 'woocommerce_get_checkout_url', $this->checkout_url_filter );

		$this->reset_runtime();
	}

	/**
	 * Restore global state and delete test fixtures.
	 */
	public function tearDown(): void {
		try {
			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}
			wc_clear_notices();

			foreach ( array_reverse( $this->coupon_ids ) as $coupon_id ) {
				$coupon = new \WC_Coupon( $coupon_id );
				if ( $coupon->get_id() ) {
					$coupon->delete( true );
				}
			}

			foreach ( array_reverse( $this->product_ids ) as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$product->delete( true );
				}
			}
		} finally {
			remove_filter( 'woocommerce_get_cart_url', $this->cart_url_filter );
			remove_filter( 'woocommerce_get_checkout_url', $this->checkout_url_filter );

			$_GET    = $this->original_get;
			$_COOKIE = $this->original_cookie;

			if ( $this->had_query_string ) {
				$_SERVER['QUERY_STRING'] = $this->original_query_string;
			} else {
				unset( $_SERVER['QUERY_STRING'] );
			}

			wp_set_current_user( $this->original_user_id );
			WC()->session = $this->original_session;
			WC()->cart    = $this->original_cart;

			parent::tearDown();
		}
	}

	/**
	 * @testdox Installing-mode requests queue the endpoint rewrite without replacing persisted rules.
	 */
	public function test_endpoint_rewrite_is_deferred_during_installing_mode(): void {
		global $wp_rewrite;

		$original_installing     = wp_installing();
		$original_rules          = get_option( 'rewrite_rules', null );
		$original_queue          = get_option( 'woocommerce_queue_flush_rewrite_rules', null );
		$original_top_rules      = $wp_rewrite->extra_rules_top;
		$persisted_rewrite_rules = array( '^third-party/?$' => 'index.php?third-party=1' );

		update_option( 'rewrite_rules', $persisted_rewrite_rules );
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		wp_installing( true );

		try {
			( new CheckoutLink() )->add_checkout_link_endpoint();
			$this->assertSame( $persisted_rewrite_rules, get_option( 'rewrite_rules' ), 'Installing mode must preserve the complete rules from the prior normal request.' );

			wp_installing( false );

			$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ), 'Installing mode should queue the missing checkout-link rule.' );
			$this->assertArrayHasKey( '^checkout-link$', $wp_rewrite->extra_rules_top, 'The endpoint should still register its rule for the current request.' );
		} finally {
			wp_installing( false );
			delete_option( 'rewrite_rules' );
			delete_option( 'woocommerce_queue_flush_rewrite_rules' );
			if ( null !== $original_rules ) {
				add_option( 'rewrite_rules', $original_rules );
			}
			if ( null !== $original_queue ) {
				add_option( 'woocommerce_queue_flush_rewrite_rules', $original_queue );
			}
			$wp_rewrite->extra_rules_top = $original_top_rules;
			wp_installing( $original_installing );
		}
	}

	/**
	 * @testdox Adds products and a coupon and includes a guest session token in the Checkout URL.
	 */
	public function test_products_and_coupon_are_added_and_guest_token_in_url(): void {
		$product_ids = $this->create_products( 3 );
		$this->create_coupon( 'test-coupon' );
		$this->set_request(
			array(
				'products'      => implode( ',', $product_ids ),
				'coupon'        => 'test-coupon',
				'checkout-link' => '1',
				'utm_source'    => 'checkout-link-test',
			)
		);

		$url   = $this->get_checkout_link();
		$query = $this->get_url_query( $url );

		$this->assertSame(
			array_combine( $product_ids, array( 1, 1, 1 ) ),
			$this->get_cart_product_quantities(),
			'The guest cart should contain each requested product with its exact quantity.'
		);
		$this->assertSame( array( 'test-coupon' ), WC()->cart->get_applied_coupons(), 'The requested coupon should be applied.' );
		$this->assertSame( wc_get_checkout_url(), remove_query_arg( array( 'utm_source', 'session' ), $url ), 'The redirect should use the Checkout URL.' );
		$this->assertSame( 'checkout-link-test', $query['utm_source'] ?? null, 'Unrelated query arguments should be preserved.' );
		$this->assertArrayNotHasKey( 'products', $query, 'The products argument should be stripped from the redirect.' );
		$this->assertArrayNotHasKey( 'coupon', $query, 'The coupon argument should be stripped from the redirect.' );
		$this->assertArrayNotHasKey( 'checkout-link', $query, 'The endpoint argument should be stripped from the redirect.' );
		$this->assertNotSame( '', $query['session'] ?? '', 'Guest redirects should contain a non-empty session token.' );
		$this->assertTrue( CartTokenUtils::validate_cart_token( (string) $query['session'] ), 'The guest session token should be valid.' );
	}

	/**
	 * @testdox Parses quantities, lets the last duplicate win, and discards malformed or zero entries.
	 */
	public function test_parses_quantities_and_discards_invalid_entries(): void {
		$product_ids = $this->create_products( 2 );
		$this->set_request(
			array(
				'products' => sprintf( '%1$d:2,abc,0:4,%2$d:0,%1$d:5,%2$d:3', $product_ids[0], $product_ids[1] ),
			)
		);

		$this->assertSame(
			array(
				$product_ids[0] => 5,
				$product_ids[1] => 3,
			),
			$this->get_products_from_checkout_link(),
			'Only valid products and quantities should remain, with the final duplicate quantity taking precedence.'
		);
	}

	/**
	 * @testdox Rejects a product list containing only malformed or zero entries.
	 */
	public function test_rejects_malformed_product_list(): void {
		$this->set_request( array( 'products' => 'abc,0,2:0' ) );

		$this->assertFalse( $this->validate_checkout_link(), 'A checkout link without any valid product entries should be rejected.' );
	}

	/**
	 * @testdox Keeps valid products and adds the exact notice when a coupon is invalid.
	 */
	public function test_invalid_coupon_keeps_valid_products_and_adds_exact_notice(): void {
		$product_id = $this->create_products( 1 )[0];
		$this->set_request(
			array(
				'products' => (string) $product_id,
				'coupon'   => 'INVALID_COUPON',
			)
		);

		$url = $this->get_checkout_link();

		$this->assertSame( array( $product_id => 1 ), $this->get_cart_product_quantities(), 'The valid product should remain in the cart.' );
		$this->assertSame( array(), WC()->cart->get_applied_coupons(), 'An invalid coupon should not be applied.' );
		$this->assertSame( wc_get_checkout_url(), remove_query_arg( 'session', $url ), 'A valid cart should redirect to Checkout.' );
		$this->assertSame(
			array( 'Coupon &quot;invalid_coupon&quot; cannot be applied because it does not exist.' ),
			$this->get_error_notice_messages(),
			'The invalid coupon notice should be exact.'
		);
	}

	/**
	 * @testdox Keeps valid products and adds the exact notice when another product is missing.
	 */
	public function test_missing_product_keeps_valid_product_and_adds_exact_notice(): void {
		$product_id = $this->create_products( 1 )[0];
		$this->set_request( array( 'products' => $product_id . ',999999' ) );

		$url = $this->get_checkout_link();

		$this->assertSame( array( $product_id => 1 ), $this->get_cart_product_quantities(), 'The valid product should remain in the cart.' );
		$this->assertSame( wc_get_checkout_url(), remove_query_arg( 'session', $url ), 'A valid cart should redirect to Checkout.' );
		$this->assertSame(
			array( 'Product with ID &quot;999999&quot; was not found and cannot be added to the cart.' ),
			$this->get_error_notice_messages(),
			'The missing-product notice should be exact.'
		);
	}

	/**
	 * @testdox Uses the query string for a missing-product error when no session exists.
	 */
	public function test_only_missing_product_without_session_uses_query_error(): void {
		$this->set_request( array( 'products' => '999999' ) );

		$url   = $this->get_checkout_link();
		$query = $this->get_url_query( $url );

		$this->assertSame( wc_get_cart_url(), remove_query_arg( 'wc_error', $url ), 'An empty cart should redirect to the Cart URL.' );
		$this->assertSame(
			'Product with ID &quot;999999&quot; was not found and cannot be added to the cart.',
			$query['wc_error'] ?? null,
			'The missing-product error should be transported in the URL without a session.'
		);
		$this->assertSame( array(), WC()->cart->get_cart(), 'The cart should remain empty.' );
		$this->assertSame( array(), $this->get_error_notice_messages(), 'No notice should be queued without a session.' );
	}

	/**
	 * @testdox Queues missing-product and generic errors when an established session exists.
	 */
	public function test_only_missing_product_with_session_queues_notices(): void {
		$session = $this->get_session_handler();
		$session->set_customer_session_cookie( true );
		$this->set_request( array( 'products' => '999999' ) );

		$url = $this->get_checkout_link();

		$this->assertSame( wc_get_cart_url(), $url, 'An empty cart with a session should redirect to the Cart URL without query transport.' );
		$this->assertArrayNotHasKey( 'wc_error', $this->get_url_query( $url ), 'The error should not be duplicated in the URL.' );
		$this->assertSame( array(), WC()->cart->get_cart(), 'The cart should remain empty.' );
		$this->assertSame(
			array(
				'Product with ID &quot;999999&quot; was not found and cannot be added to the cart.',
				'The provided checkout link was out of date or invalid. No products were added to the cart.',
			),
			$this->get_error_notice_messages(),
			'Both empty-cart errors should be queued in order.'
		);
	}

	/**
	 * @testdox Omits the session token for a logged-in customer while preserving products and coupon.
	 */
	public function test_logged_in_checkout_omits_session_token(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $user_id );
		$this->reset_runtime( $user_id );

		$product_ids = $this->create_products( 2 );
		$this->create_coupon( 'logged-in-coupon' );
		$this->set_request(
			array(
				'products' => implode( ',', $product_ids ),
				'coupon'   => 'logged-in-coupon',
			)
		);

		$url = $this->get_checkout_link();

		$this->assertSame(
			array_combine( $product_ids, array( 1, 1 ) ),
			$this->get_cart_product_quantities(),
			'The logged-in cart should contain each requested product.'
		);
		$this->assertSame( array( 'logged-in-coupon' ), WC()->cart->get_applied_coupons(), 'The requested coupon should be applied.' );
		$this->assertSame( wc_get_checkout_url(), $url, 'A logged-in customer should receive the Checkout URL without a session token.' );
		$this->assertArrayNotHasKey( 'session', $this->get_url_query( $url ), 'Logged-in redirects should not contain a session token.' );
	}

	/**
	 * Reset WooCommerce cart and session state.
	 *
	 * @param int $user_id Current user ID.
	 * @return void
	 */
	private function reset_runtime( int $user_id = 0 ): void {
		wp_set_current_user( $user_id );
		$_GET = array();

		foreach ( array_keys( $_COOKIE ) as $cookie_name ) {
			if ( str_starts_with( $cookie_name, 'wp_woocommerce_session_' ) ) {
				unset( $_COOKIE[ $cookie_name ] );
			}
		}

		WC()->session = new \WC_Session_Handler();
		WC()->session->init_session_cookie();
		WC()->cart = new \WC_Cart();
		wc_clear_notices();
	}

	/**
	 * Create simple products and track them for cleanup.
	 *
	 * @param int $count Number of products.
	 * @return int[]
	 */
	private function create_products( int $count ): array {
		$product_ids = array();

		for ( $index = 0; $index < $count; $index++ ) {
			$product_id          = \WC_Helper_Product::create_simple_product()->get_id();
			$product_ids[]       = $product_id;
			$this->product_ids[] = $product_id;
		}

		return $product_ids;
	}

	/**
	 * Create a coupon and track it for cleanup.
	 *
	 * @param string $code Coupon code.
	 * @return \WC_Coupon
	 */
	private function create_coupon( string $code ): \WC_Coupon {
		$coupon             = CouponHelper::create_coupon( $code );
		$this->coupon_ids[] = $coupon->get_id();

		return $coupon;
	}

	/**
	 * Set request globals from query arguments.
	 *
	 * @param array<string, string> $query Request arguments.
	 * @return void
	 */
	private function set_request( array $query ): void {
		$_GET                    = $query;
		$_SERVER['QUERY_STRING'] = http_build_query( $query );
	}

	/**
	 * Get product quantities from the cart.
	 *
	 * @return array<int, int>
	 */
	private function get_cart_product_quantities(): array {
		$quantities = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$quantities[ (int) $cart_item['product_id'] ] = (int) $cart_item['quantity'];
		}

		return $quantities;
	}

	/**
	 * Get decoded query arguments from a URL.
	 *
	 * @param string $url URL to parse.
	 * @return array<string, string>
	 */
	private function get_url_query( string $url ): array {
		$query        = array();
		$scalar_query = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		foreach ( $query as $key => $value ) {
			if ( is_string( $key ) && is_string( $value ) ) {
				$scalar_query[ $key ] = $value;
			}
		}

		return $scalar_query;
	}

	/**
	 * Get queued error notice messages.
	 *
	 * @return string[]
	 */
	private function get_error_notice_messages(): array {
		return array_values( wp_list_pluck( wc_get_notices( 'error' ), 'notice' ) );
	}

	/**
	 * Get the real isolated session handler.
	 *
	 * @return \WC_Session_Handler
	 */
	private function get_session_handler(): \WC_Session_Handler {
		$session = WC()->session;

		if ( ! $session instanceof \WC_Session_Handler ) {
			throw new \RuntimeException( 'The isolated Checkout Link runtime requires WC_Session_Handler.' );
		}

		return $session;
	}

	/**
	 * Invoke a protected CheckoutLink method through a reusable test seam.
	 *
	 * @param string $method Method name.
	 * @return mixed
	 */
	private function invoke_checkout_link_method( string $method ) {
		$reflection = new \ReflectionMethod( $this->sut, $method );
		$reflection->setAccessible( true );

		return $reflection->invoke( $this->sut );
	}

	/**
	 * Get parsed checkout-link products.
	 *
	 * @return array<int, int>
	 */
	private function get_products_from_checkout_link(): array {
		return (array) $this->invoke_checkout_link_method( 'get_products_from_checkout_link' );
	}

	/**
	 * Validate the checkout-link request.
	 *
	 * @return bool
	 */
	private function validate_checkout_link(): bool {
		return (bool) $this->invoke_checkout_link_method( 'validate_checkout_link' );
	}

	/**
	 * Build the checkout-link redirect.
	 *
	 * @return string
	 */
	private function get_checkout_link(): string {
		return (string) $this->invoke_checkout_link_method( 'get_checkout_link' );
	}
}
