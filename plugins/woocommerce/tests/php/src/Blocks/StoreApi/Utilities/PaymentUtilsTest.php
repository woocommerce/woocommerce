<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\Internal\Checkout\PaymentMethodPreselection;
use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentUtils class.
 */
class PaymentUtilsTest extends WC_Unit_Test_Case {

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
	 * @testdox Should return the gateway remembered in the session when preselection is on.
	 */
	public function test_default_payment_method_uses_the_session_when_preselection_is_on(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'yes' );

		// BACS is first in the enabled list, so only the session read can produce COD.
		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should keep the shopper's own choice from the session when preselection is off.
	 */
	public function test_default_payment_method_keeps_the_shoppers_choice_when_preselection_is_off(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'no' );

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should not fall back to the first gateway when preselection is off and nothing was chosen.
	 */
	public function test_default_payment_method_is_empty_when_preselection_is_off_and_nothing_was_chosen(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'no' );
		WC()->session->set( 'chosen_payment_method', null );

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return the gateway of the customer's default saved card when preselection is on, as before.
	 */
	public function test_default_payment_method_uses_the_saved_card_when_preselection_is_on(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'yes' );
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'bacs' );

		// The session holds COD, so only the saved-card branch can produce BACS.
		$this->assertSame( 'bacs', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should skip the customer's default saved card when preselection is off and nothing was chosen.
	 */
	public function test_default_payment_method_skips_the_saved_card_when_preselection_is_off(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'no' );
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'bacs' );
		WC()->session->set( 'chosen_payment_method', null );

		$this->assertSame( '', PaymentUtils::get_default_payment_method() );
	}

	/**
	 * @testdox Should return the shopper's own choice over their default saved card when preselection is off.
	 */
	public function test_default_payment_method_prefers_the_shoppers_choice_over_the_saved_card_when_preselection_is_off(): void {
		update_option( PaymentMethodPreselection::OPTION_NAME, 'no' );
		$this->create_default_saved_card( $this->login_as_role( 'customer' ), 'bacs' );

		$this->assertSame( 'cod', PaymentUtils::get_default_payment_method() );
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
