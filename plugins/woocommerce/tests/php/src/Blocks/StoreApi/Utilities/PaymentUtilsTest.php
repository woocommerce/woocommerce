<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentUtils class.
 */
class PaymentUtilsTest extends WC_Unit_Test_Case {

	const FILTER = 'woocommerce_default_payment_method';

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
	 * @testdox Should fall back to the first enabled gateway without a saved card or a session choice, as before.
	 */
	public function test_default_payment_method_falls_back_to_the_first_enabled_gateway_without_a_callback(): void {
		WC()->session->set( 'chosen_payment_method', null );

		$this->assertSame( 'bacs', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should keep an unavailable session choice without a callback, as before.
	 */
	public function test_default_payment_method_keeps_an_unavailable_session_choice_without_a_callback(): void {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'hide_cod' ) );

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return nothing when the callback returns nothing, even over a session choice.
	 */
	public function test_default_payment_method_is_empty_when_the_callback_returns_nothing(): void {
		add_filter( self::FILTER, '__return_empty_string' );

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return the available gateway the callback picks.
	 */
	public function test_default_payment_method_is_the_gateway_the_callback_picks(): void {
		add_filter(
			self::FILTER,
			function () {
				return 'bacs';
			}
		);

		$this->assertSame( 'bacs', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return nothing when the callback answers with something that isn't a gateway id.
	 *
	 * @testWith ["nope"]
	 *           [null]
	 *           [false]
	 *           [0]
	 *           [["bacs"]]
	 *
	 * @param mixed $answer What the callback returns.
	 */
	public function test_default_payment_method_returns_nothing_when_the_callback_answer_is_invalid( $answer ): void {
		add_filter(
			self::FILTER,
			function () use ( $answer ) {
				return $answer;
			}
		);

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return nothing when the callback picks a gateway that is not enabled.
	 */
	public function test_default_payment_method_returns_nothing_when_the_callback_picks_a_disabled_gateway(): void {
		add_filter(
			self::FILTER,
			function () {
				return 'paypal';
			}
		);

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return nothing when the callback picks a gateway that is enabled but not available for this cart.
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
	 * @testdox Should pass an unchanged answer through even when that gateway is not available, as without a callback.
	 */
	public function test_default_payment_method_passes_an_unchanged_answer_through(): void {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'hide_cod' ) );
		add_filter(
			self::FILTER,
			function ( $payment_method ) {
				return $payment_method;
			}
		);

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should pass the session choice and the available gateways to the callback.
	 */
	public function test_callback_receives_the_session_choice_and_the_available_gateways(): void {
		$received = array();
		add_filter(
			self::FILTER,
			function ( $payment_method, $available_gateways ) use ( &$received ) {
				$received = array( $payment_method, $available_gateways );
				return $payment_method;
			},
			10,
			2
		);

		PaymentUtils::get_default_payment_method();

		$this->assertSame( 'cod', $received[0] );
		$this->assertSame( array( 'bacs', 'cod' ), array_keys( $received[1] ) );
		$this->assertContainsOnlyInstancesOf( \WC_Payment_Gateway::class, $received[1] );
	}

	/**
	 * @testdox Should pass the saved card's gateway to the callback when the customer has one.
	 */
	public function test_callback_receives_the_saved_card_gateway(): void {
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'bacs' );
		$received = '';
		add_filter(
			self::FILTER,
			function ( $payment_method ) use ( &$received ) {
				$received = $payment_method;
				return $payment_method;
			}
		);

		PaymentUtils::get_default_payment_method();

		$this->assertSame( 'bacs', $received );
	}

	/**
	 * @testdox Should pass the first enabled gateway to the callback without a saved card or a session choice.
	 */
	public function test_callback_receives_the_first_enabled_gateway(): void {
		WC()->session->set( 'chosen_payment_method', null );
		$received = '';
		add_filter(
			self::FILTER,
			function ( $payment_method ) use ( &$received ) {
				$received = $payment_method;
				return $payment_method;
			}
		);

		PaymentUtils::get_default_payment_method();

		$this->assertSame( 'bacs', $received );
	}

	/**
	 * @testdox Should carry the shopper's session choice through the checkout even when the callback returns nothing.
	 */
	public function test_checkout_payment_method_keeps_the_session_choice_with_a_callback(): void {
		add_filter( self::FILTER, '__return_empty_string' );

		$this->assertSame( 'cod', PaymentUtils::get_checkout_payment_method() );
	}

	/**
	 * @testdox Should fall back to the default when the session choice is enabled but not available for this cart.
	 */
	public function test_checkout_payment_method_ignores_an_unavailable_session_choice(): void {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'hide_cod' ) );
		add_filter( self::FILTER, '__return_empty_string' );

		$this->assertSame( '', PaymentUtils::get_checkout_payment_method() );
	}

	/**
	 * @testdox Should fall back to the default when the session choice is a disabled gateway.
	 */
	public function test_checkout_payment_method_ignores_a_disabled_session_choice(): void {
		WC()->session->set( 'chosen_payment_method', 'paypal' );
		add_filter( self::FILTER, '__return_empty_string' );

		$this->assertSame( '', PaymentUtils::get_checkout_payment_method() );
	}

	/**
	 * @testdox Should fall back to the default without a session choice.
	 */
	public function test_checkout_payment_method_falls_back_to_the_default_without_a_session_choice(): void {
		WC()->session->set( 'chosen_payment_method', null );

		$this->assertSame( 'bacs', PaymentUtils::get_checkout_payment_method() );
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
