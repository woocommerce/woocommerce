<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentUtils class.
 */
class PaymentUtilsTest extends WC_Unit_Test_Case {

	const FILTER = 'woocommerce_preselected_payment_method';

	/**
	 * Gateway instances before the test; they cache their settings, which the rollback doesn't reset.
	 *
	 * @var array
	 */
	private $payment_gateways_before_test = array();

	/**
	 * Enable two gateways and remember the second one in the session, as a returning shopper would have.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->payment_gateways_before_test = WC()->payment_gateways()->payment_gateways;

		foreach ( array( 'woocommerce_bacs_settings', 'woocommerce_cod_settings' ) as $option_name ) {
			$settings            = (array) get_option( $option_name, array() );
			$settings['enabled'] = 'yes';
			update_option( $option_name, $settings );
		}
		WC()->payment_gateways()->init();

		WC()->session->set( 'chosen_payment_method', 'cod' );
	}

	/**
	 * Put back the in-memory state the base class doesn't reset: the gateway instances and the session choice.
	 */
	public function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			wp_cache_delete( 'woocommerce_bacs_settings', 'options' );
			wp_cache_delete( 'woocommerce_cod_settings', 'options' );
			WC()->payment_gateways()->payment_gateways = $this->payment_gateways_before_test;
			WC()->session->set( 'chosen_payment_method', null );
		}
	}

	/**
	 * @testdox Should return the gateway remembered in the session without a callback, as before.
	 */
	public function test_default_payment_method_follows_trunk_without_a_callback(): void {
		// BACS is first in the enabled list, so only the session read can produce COD.
		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should prefer the customer's default saved card over the session without a callback, as before.
	 */
	public function test_default_payment_method_prefers_the_saved_card_without_a_callback(): void {
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'bacs' );

		$this->assertSame( 'bacs', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should select nothing when the callback returns an empty string.
	 */
	public function test_default_payment_method_is_empty_when_the_callback_returns_nothing(): void {
		WC()->session->set( 'chosen_payment_method', null );
		add_filter( self::FILTER, '__return_empty_string' );

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return the gateway the callback picks.
	 */
	public function test_default_payment_method_is_the_gateway_the_callback_picks(): void {
		WC()->session->set( 'chosen_payment_method', null );
		// BACS is first, so only the callback can produce COD.
		add_filter(
			self::FILTER,
			function () {
				return 'cod';
			}
		);

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should select nothing when the callback answer is not a gateway id.
	 * @testWith ["nope"]
	 *           [null]
	 *           [true]
	 *
	 * @param mixed $answer What the callback returns.
	 */
	public function test_default_payment_method_returns_nothing_when_the_callback_answer_is_invalid( $answer ): void {
		WC()->session->set( 'chosen_payment_method', null );
		add_filter(
			self::FILTER,
			function () use ( $answer ) {
				return $answer;
			}
		);

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should select nothing when the callback picks a gateway that is not enabled.
	 */
	public function test_default_payment_method_returns_nothing_when_the_callback_picks_a_disabled_gateway(): void {
		WC()->session->set( 'chosen_payment_method', null );
		// PayPal is registered but not enabled, so it is never available.
		add_filter(
			self::FILTER,
			function () {
				return 'paypal';
			}
		);

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should select nothing when the callback picks a gateway that is enabled but not available for this cart.
	 */
	public function test_default_payment_method_returns_nothing_when_the_callback_picks_an_unavailable_gateway(): void {
		WC()->session->set( 'chosen_payment_method', null );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'hide_cod' ) );
		add_filter(
			self::FILTER,
			function () {
				return 'cod';
			}
		);

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should ignore a session choice that is enabled but not available for this cart and ask the callback.
	 */
	public function test_default_payment_method_ignores_an_unavailable_session_choice(): void {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'hide_cod' ) );
		$received = '';
		add_filter(
			self::FILTER,
			function ( $method ) use ( &$received ) {
				$received = $method;
				return 'bacs';
			}
		);

		$this->assertSame( 'bacs', PaymentUtils::get_default_payment_method() );
		$this->assertSame( 'bacs', $received, 'The callback sees the first available gateway, not the unavailable session choice.' );
	}

	/**
	 * @testdox Should ignore a session choice for a disabled gateway and ask the callback instead.
	 */
	public function test_default_payment_method_ignores_a_disabled_session_choice(): void {
		WC()->session->set( 'chosen_payment_method', 'paypal' );
		add_filter(
			self::FILTER,
			function () {
				return 'bacs';
			}
		);

		$this->assertSame( 'bacs', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should keep the shopper's own choice and not ask the callback.
	 */
	public function test_default_payment_method_keeps_the_shoppers_choice_and_skips_the_callback(): void {
		$called = false;
		add_filter(
			self::FILTER,
			function () use ( &$called ) {
				$called = true;
				return '';
			}
		);

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
		$this->assertFalse( $called, 'The shopper already chose, so the filter must not run.' );
	}

	/**
	 * @testdox Should keep the shopper's own choice over their default saved card with a callback attached.
	 */
	public function test_default_payment_method_keeps_the_shoppers_choice_over_their_saved_card_with_a_callback(): void {
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'bacs' );
		add_filter( self::FILTER, '__return_empty_string' );

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should pass the saved card's gateway and the available gateways to the callback.
	 */
	public function test_callback_receives_the_saved_card_gateway_and_the_available_gateways(): void {
		WC()->session->set( 'chosen_payment_method', null );
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'cod' );
		$received = array();
		add_filter(
			self::FILTER,
			function ( $method, $gateways ) use ( &$received ) {
				$received = array( $method, array_keys( $gateways ) );
				return $method;
			},
			10,
			2
		);

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
		$this->assertSame( array( 'cod', array( 'bacs', 'cod' ) ), $received );
	}

	/**
	 * @testdox Should pass the first available gateway to the callback when there is no saved card.
	 */
	public function test_callback_receives_the_first_available_gateway_without_a_saved_card(): void {
		WC()->session->set( 'chosen_payment_method', null );
		$received = '';
		add_filter(
			self::FILTER,
			function ( $method ) use ( &$received ) {
				$received = $method;
				return '';
			}
		);

		PaymentUtils::get_default_payment_method();

		$this->assertSame( 'bacs', $received );
	}

	/**
	 * @testdox Should pass the first available gateway when the saved card's gateway is not available.
	 */
	public function test_callback_receives_the_first_gateway_when_the_saved_card_gateway_is_unavailable(): void {
		WC()->session->set( 'chosen_payment_method', null );
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'paypal' );
		$received = '';
		add_filter(
			self::FILTER,
			function ( $method ) use ( &$received ) {
				$received = $method;
				return '';
			}
		);

		PaymentUtils::get_default_payment_method();

		$this->assertSame( 'bacs', $received );
	}

	/**
	 * Hide COD from the available gateways, as a shipping restriction would, while it stays enabled.
	 *
	 * @param array $gateways Available gateways keyed by id.
	 * @return array
	 */
	public function hide_cod( $gateways ) {
		unset( $gateways['cod'] );
		return $gateways;
	}

	/**
	 * Save a default card for the customer on the given gateway.
	 *
	 * @param int    $user_id    Customer id.
	 * @param string $gateway_id Gateway the card belongs to.
	 */
	private function create_default_saved_card( int $user_id, string $gateway_id ): void {
		$token = new \WC_Payment_Token_CC();
		$token->set_gateway_id( $gateway_id );
		$token->set_user_id( $user_id );
		$token->set_token( 'tok_test' );
		$token->set_last4( '4242' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( (string) ( (int) gmdate( 'Y' ) + 2 ) );
		$token->set_card_type( 'visa' );
		$token->set_default( true );
		$token->save();
	}
}
