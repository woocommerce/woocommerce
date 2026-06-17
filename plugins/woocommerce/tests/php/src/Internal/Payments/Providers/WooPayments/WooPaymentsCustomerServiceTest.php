<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCustomerService;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsCustomerService class.
 */
class WooPaymentsCustomerServiceTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( WC()->session ) {
			WC()->session->set( 'wcpay_customer_id', null );
		}

		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Logged-in shoppers should use mode-aware user storage for WooPayments customer IDs.
	 */
	public function test_get_or_create_customer_id_uses_mode_aware_user_storage_for_logged_in_customers(): void {
		$user_id    = $this->factory->user->create( array( 'user_login' => 'merchant' ) );
		$order      = $this->create_checkout_order( $user_id );
		$api_client = $this->create_customer_api_client( array( 'cus_live', 'cus_test' ) );

		$live_sut = $this->create_sut( false, $api_client );
		$this->assertSame( 'cus_live', $live_sut->get_or_create_customer_id_for_order( $order ) );
		$this->assertSame( 'cus_live', get_user_option( '_wcpay_customer_id_live', $user_id ) );

		delete_user_option( $user_id, '_wcpay_customer_id_live' );

		$test_sut = $this->create_sut( true, $api_client );
		$this->assertSame( 'cus_test', $test_sut->get_or_create_customer_id_for_order( $order ) );
		$this->assertSame( 'cus_test', get_user_option( '_wcpay_customer_id_test', $user_id ) );
	}

	/**
	 * @testdox Guest shoppers should use session storage for WooPayments customer IDs.
	 */
	public function test_get_or_create_customer_id_uses_session_storage_for_guests(): void {
		$order      = $this->create_checkout_order();
		$api_client = $this->create_customer_api_client( array( 'cus_guest' ) );

		$sut = $this->create_sut( false, $api_client );

		$this->assertSame( 'cus_guest', $sut->get_or_create_customer_id_for_order( $order ) );
		$this->assertSame( 'cus_guest', WC()->session ? WC()->session->get( 'wcpay_customer_id' ) : null );
		$this->assertSame( 'cus_guest', $sut->get_or_create_customer_id_for_order( $order ) );
	}

	/**
	 * @testdox Orders with WooPayments customer meta should reuse that customer before user storage.
	 */
	public function test_get_or_create_customer_id_reuses_order_customer_meta_before_user_storage(): void {
		$user_id    = $this->factory->user->create( array( 'user_login' => 'renewal-customer' ) );
		$order      = $this->create_checkout_order( $user_id );
		$api_client = $this->create_customer_api_client( array( 'cus_new' ) );

		update_user_option( $user_id, '_wcpay_customer_id_live', 'cus_user' );
		$order->update_meta_data( '_stripe_customer_id', 'cus_order' );
		$order->save();

		$sut = $this->create_sut( false, $api_client );

		$this->assertSame( 'cus_order', $sut->get_or_create_customer_id_for_order( $order ) );
		$this->assertSame( 'cus_order', get_user_option( '_wcpay_customer_id_live', $user_id ) );
	}

	/**
	 * @testdox Recreating a missing customer should replace the persisted customer ID.
	 */
	public function test_recreate_customer_replaces_a_missing_customer_id_and_updates_storage(): void {
		$user_id = $this->factory->user->create( array( 'user_login' => 'recreate-me' ) );
		$order   = $this->create_checkout_order( $user_id );

		update_user_option( $user_id, '_wcpay_customer_id_live', 'cus_old' );

		$api_client = $this->create_customer_api_client( array( 'cus_new' ) );

		$sut = $this->create_sut( false, $api_client );

		$this->assertSame( 'cus_new', $sut->recreate_customer_for_order( $order ) );
		$this->assertSame( 'cus_new', get_user_option( '_wcpay_customer_id_live', $user_id ) );
	}

	/**
	 * Create a customer service System Under Test.
	 *
	 * @param bool                 $test_mode  Whether test mode is enabled.
	 * @param WooPaymentsApiClient $api_client Native API client mock.
	 * @return WooPaymentsCustomerService
	 */
	private function create_sut( bool $test_mode, WooPaymentsApiClient $api_client ): WooPaymentsCustomerService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled' ) )
			->getMock();

		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );

		$sut = new WooPaymentsCustomerService();
		$sut->init( $api_client, $account_service );

		return $sut;
	}

	/**
	 * Create a concrete API-client double for customer creation.
	 *
	 * @param string[] $customer_ids Customer IDs to return in order.
	 * @return WooPaymentsApiClient
	 */
	private function create_customer_api_client( array $customer_ids ): WooPaymentsApiClient {
		return new class( $customer_ids ) extends WooPaymentsApiClient {
			/**
			 * Customer IDs to return.
			 *
			 * @var string[]
			 */
			private array $customer_ids;

			/**
			 * Constructor.
			 *
			 * @param string[] $customer_ids Customer IDs to return.
			 */
			public function __construct( array $customer_ids ) {
				$this->customer_ids = $customer_ids;
			}

			/**
			 * Create a customer.
			 *
			 * @param array<string,mixed> $customer_data Customer data.
			 * @return string
			 */
			public function create_customer( array $customer_data ): string {
				unset( $customer_data );

				return (string) array_shift( $this->customer_ids );
			}

			/**
			 * Update a customer.
			 *
			 * @param string              $customer_id Customer ID.
			 * @param array<string,mixed> $customer_data Customer data.
			 */
			public function update_customer( string $customer_id, array $customer_data = array() ): void {
				unset( $customer_id, $customer_data );
			}
		};
	}

	/**
	 * Create a checkout order fixture.
	 *
	 * @param int $customer_id Customer ID.
	 * @return WC_Order
	 */
	private function create_checkout_order( int $customer_id = 0 ): WC_Order {
		$order = wc_create_order();
		$order->set_customer_id( $customer_id );
		$order->set_billing_first_name( 'Ada' );
		$order->set_billing_last_name( 'Lovelace' );
		$order->set_billing_email( 'ada@example.com' );
		$order->set_billing_phone( '+40123456789' );
		$order->set_billing_address_1( '1 Core St' );
		$order->set_billing_city( 'Bucharest' );
		$order->set_billing_postcode( '010101' );
		$order->set_billing_country( 'RO' );
		$order->set_shipping_first_name( 'Ada' );
		$order->set_shipping_last_name( 'Lovelace' );
		$order->set_shipping_address_1( '1 Core St' );
		$order->set_shipping_city( 'Bucharest' );
		$order->set_shipping_postcode( '010101' );
		$order->set_shipping_country( 'RO' );
		$order->save();

		return $order;
	}
}
