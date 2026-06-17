<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTokenizedCartSessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use WC_Unit_Test_Case;

/**
 * Tests for the native WooPayments tokenized cart session handler.
 */
class WooPaymentsTokenizedCartSessionHandlerTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset(
			$_SERVER['HTTP_X_WOOPAYMENTS_TOKENIZED_CART_SESSION'],
			$_SERVER['HTTP_X_WOOPAYMENTS_TOKENIZED_CART_IS_EPHEMERAL_CART']
		);
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Should create an isolated guest session when no tokenized session header is present.
	 */
	public function test_creates_isolated_guest_session_without_cookie_header(): void {
		$handler = new WooPaymentsTokenizedCartSessionHandler();
		$handler->init();

		$this->assertStringStartsWith( 't_', $handler->get_customer_id() );
		$this->assertSame( array(), $handler->get( 'cart' ) );
	}

	/**
	 * @testdox Should create an isolated tokenized guest session for logged-in shoppers.
	 */
	public function test_creates_isolated_guest_session_for_logged_in_shopper(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );

		$handler = new WooPaymentsTokenizedCartSessionHandler();
		$handler->init();

		$this->assertNotSame( (string) $user_id, $handler->get_customer_id() );
		$this->assertStringStartsWith( 't_', $handler->get_customer_id() );
	}

	/**
	 * @testdox Should restore the isolated session from a valid tokenized session header.
	 */
	public function test_restores_isolated_session_from_valid_token(): void {
		$_SERVER['HTTP_X_WOOPAYMENTS_TOKENIZED_CART_SESSION'] = JsonWebToken::create(
			array(
				'session_id' => 't_existing_native_session',
				'exp'        => time() + HOUR_IN_SECONDS,
				'iss'        => 'woopayments/product-page',
			),
			'@' . wp_salt()
		);

		$handler = new WooPaymentsTokenizedCartSessionHandler();
		$handler->init();

		$this->assertSame( 't_existing_native_session', $handler->get_customer_id() );
		$this->assertSame( array(), $handler->get( 'cart' ) );
	}

	/**
	 * @testdox Should save and reload tokenized cart data by tokenized session ID.
	 */
	public function test_saves_and_reloads_isolated_session_data(): void {
		$handler = new WooPaymentsTokenizedCartSessionHandler();
		$handler->init();
		$session_id = $handler->get_customer_id();

		$handler->set( 'cart', array( 'test-key' => array( 'quantity' => 2 ) ) );
		$handler->save_data();

		$_SERVER['HTTP_X_WOOPAYMENTS_TOKENIZED_CART_SESSION'] = JsonWebToken::create(
			array(
				'session_id' => $session_id,
				'exp'        => time() + HOUR_IN_SECONDS,
				'iss'        => 'woopayments/product-page',
			),
			'@' . wp_salt()
		);

		$loaded = new WooPaymentsTokenizedCartSessionHandler();
		$loaded->init();

		$this->assertSame( array( 'test-key' => array( 'quantity' => 2 ) ), $loaded->get( 'cart' ) );
	}
}
