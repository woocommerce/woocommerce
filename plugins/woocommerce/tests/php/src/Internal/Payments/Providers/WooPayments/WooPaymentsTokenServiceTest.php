<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPaymentMethodDetailsService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTokenService;
use RuntimeException;
use WC_Order;
use WC_Payment_Token_CC;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsTokenService class.
 */
class WooPaymentsTokenServiceTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'wcpay_pm_customer_1' );
		delete_option( 'wcpay_pm_customer_2' );
		delete_option( 'not_wcpay_pm_customer' );
		remove_all_filters( 'pre_option_wcpay_pm_customer_1' );
		parent::tearDown();
	}

	/**
	 * @testdox Should resolve a WooCommerce payment token ID to its provider payment method ID.
	 */
	public function test_resolves_payment_method_id_from_owned_token(): void {
		$user_id = $this->factory()->user->create();
		$token   = $this->create_card_token( $user_id, OrderPaymentStore::GATEWAY_ID, 'pm_saved' );
		$sut     = $this->create_service();

		$result = $sut->resolve_payment_method_id_from_token_id( (string) $token->get_id(), $user_id );

		$this->assertSame( 'pm_saved', $result, 'Owned WooPayments tokens should resolve to the provider payment method ID.' );
	}

	/**
	 * @testdox Should reject tokens that belong to another user or gateway.
	 */
	public function test_rejects_unowned_or_wrong_gateway_tokens(): void {
		$user_id       = $this->factory()->user->create();
		$other_user_id = $this->factory()->user->create();
		$owned_token   = $this->create_card_token( $user_id, OrderPaymentStore::GATEWAY_ID, 'pm_owned' );
		$other_gateway = $this->create_card_token( $user_id, 'cheque', 'pm_cheque' );
		$sut           = $this->create_service();

		$this->assertSame( '', $sut->resolve_payment_method_id_from_token_id( (string) $owned_token->get_id(), $other_user_id ), 'Tokens owned by another customer should not resolve.' );
		$this->assertSame( '', $sut->resolve_payment_method_id_from_token_id( (string) $other_gateway->get_id(), $user_id ), 'Tokens for another gateway should not resolve.' );
		$this->assertSame( '', $sut->resolve_payment_method_id_from_token_id( '999999', $user_id ), 'Missing token IDs should not resolve.' );
	}

	/**
	 * @testdox Should resolve native WooPayments tokens already attached to a renewal order.
	 */
	public function test_resolves_payment_method_id_from_order_attached_token(): void {
		$user_id       = $this->factory()->user->create();
		$other_user_id = $this->factory()->user->create();
		$token         = $this->create_card_token( $user_id, OrderPaymentStore::GATEWAY_ID, 'pm_attached' );
		$unattached    = $this->create_card_token( $user_id, OrderPaymentStore::GATEWAY_ID, 'pm_unattached' );
		$wrong_gateway = $this->create_card_token( $user_id, 'cheque', 'pm_cheque' );
		$order         = wc_create_order();
		$sut           = $this->create_service();

		$order->set_customer_id( $other_user_id );
		$order->add_payment_token( $token );
		$order->add_payment_token( $wrong_gateway );
		$order->save();

		$this->assertSame( 'pm_attached', $sut->resolve_payment_method_id_from_order_token_id( (string) $token->get_id(), $order ) );
		$this->assertSame( '', $sut->resolve_payment_method_id_from_order_token_id( (string) $unattached->get_id(), $order ), 'Tokens not attached to the order should not resolve.' );
		$this->assertSame( '', $sut->resolve_payment_method_id_from_order_token_id( (string) $wrong_gateway->get_id(), $order ), 'Non-WooPayments tokens attached to the order should not resolve.' );
	}

	/**
	 * @testdox Should create a card token from WooPayments payment method details.
	 */
	public function test_creates_card_token_from_payment_method_details(): void {
		$user_id = $this->factory()->user->create();
		$sut     = $this->create_service(
			array(
				'pm_new' => array(
					'id'   => 'pm_new',
					'type' => 'card',
					'card' => array(
						'display_brand' => 'Visa',
						'brand'         => 'visa',
						'last4'         => '4242',
						'exp_month'     => 7,
						'exp_year'      => 2032,
						'wallet'        => array(
							'type' => 'apple_pay',
						),
					),
				),
			)
		);

		$token = $sut->get_or_create_card_token_for_user( 'pm_new', $user_id );

		$this->assertInstanceOf( WC_Payment_Token_CC::class, $token );
		$this->assertGreaterThan( 0, $token->get_id(), 'Created tokens should be persisted.' );
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $token->get_gateway_id() );
		$this->assertSame( $user_id, $token->get_user_id() );
		$this->assertSame( 'pm_new', $token->get_token() );
		$this->assertSame( 'visa', $token->get_card_type() );
		$this->assertSame( '4242', $token->get_last4() );
		$this->assertSame( '07', $token->get_expiry_month() );
		$this->assertSame( '2032', $token->get_expiry_year() );
		$this->assertSame( 'apple_pay', $token->get_meta( '_wcpay_wallet_type', true ) );
	}

	/**
	 * @testdox Should reuse an existing token for the same provider payment method.
	 */
	public function test_reuses_existing_customer_token(): void {
		$user_id        = $this->factory()->user->create();
		$existing_token = $this->create_card_token( $user_id, OrderPaymentStore::GATEWAY_ID, 'pm_existing' );
		$sut            = $this->create_service(
			array(
				'pm_existing' => array(
					'id'   => 'pm_existing',
					'type' => 'card',
					'card' => array(
						'brand'     => 'mastercard',
						'last4'     => '4444',
						'exp_month' => 12,
						'exp_year'  => 2031,
					),
				),
			)
		);

		$token = $sut->get_or_create_card_token_for_user( 'pm_existing', $user_id );

		$this->assertInstanceOf( WC_Payment_Token_CC::class, $token );
		$this->assertSame( $existing_token->get_id(), $token->get_id(), 'Existing tokens should be reused instead of duplicated.' );
	}

	/**
	 * @testdox Should return null when payment method details are not tokenizable card details.
	 */
	public function test_returns_null_for_missing_card_details(): void {
		$user_id = $this->factory()->user->create();
		$sut     = $this->create_service(
			array(
				'pm_incomplete' => array(
					'id'   => 'pm_incomplete',
					'type' => 'card',
					'card' => array(
						'brand'     => 'visa',
						'exp_month' => 1,
						'exp_year'  => 2030,
					),
				),
			)
		);

		$this->assertNull( $sut->get_or_create_card_token_for_user( 'pm_incomplete', $user_id ), 'Incomplete card details should not create invalid WooCommerce tokens.' );
		$this->assertNull( $sut->get_or_create_card_token_for_user( '', $user_id ), 'Empty payment method IDs should not create tokens.' );
		$this->assertNull( $sut->get_or_create_card_token_for_user( 'pm_incomplete', 0 ), 'Guest customers cannot receive saved card tokens.' );
	}

	/**
	 * @testdox Should attach a persisted token to an order.
	 */
	public function test_attaches_token_to_order(): void {
		$user_id = $this->factory()->user->create();
		$token   = $this->create_card_token( $user_id, OrderPaymentStore::GATEWAY_ID, 'pm_order' );
		$order   = wc_create_order();
		$sut     = $this->create_service();

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertTrue( $sut->attach_token_to_order( $order, $token ), 'Persisted tokens should attach to orders.' );

		$order = wc_get_order( $order->get_id() );
		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertContains( $token->get_id(), $order->get_payment_tokens(), 'The order should store the attached token ID.' );
	}

	/**
	 * @testdox Should clear preserved WooPayments payment method caches for all users.
	 */
	public function test_clear_all_cached_payment_methods_removes_preserved_cache_entries(): void {
		$user_id       = $this->factory()->user->create();
		$other_user_id = $this->factory()->user->create();
		update_user_meta( $user_id, '_wcpay_payment_methods', array( 'pm_user' ) );
		update_user_meta( $other_user_id, '_wcpay_payment_methods', array( 'pm_other' ) );
		update_user_meta( $user_id, '_not_wcpay_payment_methods', array( 'pm_keep' ) );
		update_option( 'wcpay_pm_customer_1', array( 'pm_cached_1' ) );
		update_option( 'wcpay_pm_customer_2', array( 'pm_cached_2' ) );
		update_option( 'not_wcpay_pm_customer', 'keep' );

		$sut = $this->create_service();
		$sut->clear_all_cached_payment_methods();

		$this->assertSame( '', get_user_meta( $user_id, '_wcpay_payment_methods', true ) );
		$this->assertSame( '', get_user_meta( $other_user_id, '_wcpay_payment_methods', true ) );
		$this->assertSame( array( 'pm_keep' ), get_user_meta( $user_id, '_not_wcpay_payment_methods', true ) );
		$this->assertFalse( get_option( 'wcpay_pm_customer_1' ) );
		$this->assertFalse( get_option( 'wcpay_pm_customer_2' ) );
		$this->assertSame( 'keep', get_option( 'not_wcpay_pm_customer' ) );
	}

	/**
	 * @testdox Should fail closed when a selected cached payment-method option cannot be deleted.
	 */
	public function test_clear_all_cached_payment_methods_fails_closed_when_option_delete_does_not_stick(): void {
		update_option( 'wcpay_pm_customer_1', array( 'pm_cached_1' ) );
		add_filter(
			'pre_option_wcpay_pm_customer_1',
			static function () {
				return array( 'pm_cached_1' );
			}
		);

		$sut = $this->create_service();

		$this->expectException( RuntimeException::class );

		$sut->clear_all_cached_payment_methods();
	}

	/**
	 * Create the system under test.
	 *
	 * @param array<string,array<string,mixed>> $payment_method_details Payment method details keyed by ID.
	 * @return WooPaymentsTokenService
	 */
	private function create_service( array $payment_method_details = array() ): WooPaymentsTokenService {
		$details_service = new class( $payment_method_details ) extends WooPaymentsPaymentMethodDetailsService {
			/**
			 * Payment method details keyed by ID.
			 *
			 * @var array<string,array<string,mixed>>
			 */
			private array $payment_method_details;

			/**
			 * Constructor.
			 *
			 * @param array<string,array<string,mixed>> $payment_method_details Payment method details keyed by ID.
			 */
			public function __construct( array $payment_method_details ) {
				$this->payment_method_details = $payment_method_details;
			}

			/**
			 * Get payment method details.
			 *
			 * @param string $payment_method_id Payment method ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_method_details( string $payment_method_id ): array {
				return $this->payment_method_details[ $payment_method_id ] ?? array();
			}
		};

		$sut = new WooPaymentsTokenService();
		$sut->init( $details_service );

		return $sut;
	}

	/**
	 * Create a persisted credit-card token.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $gateway_id Gateway ID.
	 * @param string $token_id   Provider token ID.
	 * @return WC_Payment_Token_CC
	 */
	private function create_card_token( int $user_id, string $gateway_id, string $token_id ): WC_Payment_Token_CC {
		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( $gateway_id );
		$token->set_user_id( $user_id );
		$token->set_token( $token_id );
		$token->set_card_type( 'visa' );
		$token->set_last4( '4242' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2030' );
		$token->save();

		return $token;
	}
}
