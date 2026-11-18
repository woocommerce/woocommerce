<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi\WooPaymentsPoc;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use WCPay\Core\Server\Request\Refund_Charge;
use WCPay\Core\Server\Response;

/**
 * POC: which WooPayments abilities can Core abilities replace, with real WooPayments code loaded?
 *
 * 1. refund-charge -> woocommerce/order-refund-create (#70935) through WC_Payment_Gateway_WCPay::process_refund().
 * 2. get-fraud-outcomes and get-authorizations -> woocommerce/orders-query with filters on the meta
 *    that WC_Payments_Order_Service writes.
 *
 * @group woopayments-poc
 */
class WooPaymentsAbilitiesReplacementPocTest extends \WC_REST_Unit_Test_Case {

	private static $administrator_id;

	/**
	 * Refund_Charge requests that reached the WooPayments API client.
	 *
	 * @var Refund_Charge[]
	 */
	private $sent_refunds = array();

	/**
	 * Puts the gateway's real API client back.
	 *
	 * @var callable|null
	 */
	private $restore_api_client;

	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	public function setUp(): void {
		global $wp_actions;
		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore
		parent::setUp();
		if ( ! class_exists( \WC_Payments::class ) ) {
			$this->markTestSkipped( 'Set WC_POC_WOOPAYMENTS_DIR and use the WooPaymentsPoc bootstrap.' );
		}
		wc_get_container()->get( AbilitiesLoader::class );
		$this->reset_registries();
		wp_set_current_user( self::$administrator_id );
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'yes' );
		add_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_woopayments_order_filters' ) );
		add_filter( 'wcpay_create_request', array( $this, 'mock_refund_request' ), 10, 2 );
		add_filter( 'pre_http_request', array( $this, 'answer_wpcom_requests' ), 10, 3 );
	}

	/**
	 * WooPayments asks WordPress.com for its fraud services while it handles an order. Answer with an empty list.
	 *
	 * @param mixed  $response Response another filter built.
	 * @param array  $args     Request arguments.
	 * @param string $url      Request URL.
	 * @return mixed
	 */
	public function answer_wpcom_requests( $response, $args, $url ) {
		unset( $args );
		if ( false === strpos( $url, 'public-api.wordpress.com' ) ) {
			return $response;
		}
		return array(
			'headers'  => array(),
			'body'     => '{}',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}

	public function tearDown(): void {
		if ( $this->restore_api_client ) {
			( $this->restore_api_client )();
		}
		remove_action( 'woocommerce_extensible_abilities_init', array( $this, 'register_woopayments_order_filters' ) );
		remove_filter( 'wcpay_create_request', array( $this, 'mock_refund_request' ), 10 );
		remove_filter( 'pre_http_request', array( $this, 'answer_wpcom_requests' ), 10 );
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'no' );
		$this->reset_registries();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * What WooPayments would register instead of get-fraud-outcomes and get-authorizations.
	 */
	public function register_woopayments_order_filters(): void {
		$meta_filter = static function ( string $filter, string $meta_key, array $schema ): void {
			ExtensibleAbilities::register_query_filter(
				array(
					'resource'  => 'order',
					'namespace' => 'woocommerce-payments',
					'filter'    => $filter,
					'schema'    => $schema,
					'callback'  => static fn( string $value ) => array(
						'meta_query' => array( // phpcs:ignore
							array(
								'key'   => $meta_key,
								'value' => $value,
							),
						),
					),
				)
			);
		};
		$meta_filter(
			'fraud_outcome_status',
			\WC_Payments_Order_Service::WCPAY_FRAUD_OUTCOME_STATUS_META_KEY,
			array(
				'type' => 'string',
				'enum' => array( 'allow', 'review', 'block' ),
			)
		);
		$meta_filter( 'intention_status', \WC_Payments_Order_Service::INTENTION_STATUS_META_KEY, array( 'type' => 'string' ) );

		$order_service = \WC_Payments::get_order_service();
		ExtensibleAbilities::register_field(
			array(
				'resource'      => 'order',
				'namespace'     => 'woocommerce-payments',
				'field'         => 'charge_id',
				'output_schema' => array( 'type' => 'string' ),
				'get_callback'  => static fn( \WC_Order $order ) => $order_service->get_charge_id_for_order( $order ) ?: null,
			)
		);
	}

	/**
	 * Stand in for the WooPayments server: record the Refund_Charge request and answer it.
	 *
	 * @param mixed  $request    Request another filter built, or null.
	 * @param string $class_name Request class.
	 * @return mixed
	 */
	public function mock_refund_request( $request, $class_name ) {
		if ( null !== $request || Refund_Charge::class !== $class_name ) {
			return $request;
		}
		$api_client = $this->createMock( \WC_Payments_API_Client::class );
		$mock       = $this->getMockBuilder( Refund_Charge::class )
			->setConstructorArgs( array( $api_client, $this->createMock( \WC_Payments_Http_Interface::class ) ) )
			->onlyMethods( array( 'format_response' ) )
			->getMock();
		$mock->method( 'format_response' )->willReturnCallback(
			function () use ( $mock ) {
				$this->sent_refunds[] = $mock;
				return new Response(
					array(
						'id'                  => 're_poc',
						'amount'              => $mock->get_param( 'amount' ),
						'currency'            => 'usd',
						'status'              => 'succeeded',
						'balance_transaction' => 'txn_poc',
					)
				);
			}
		);
		return $mock;
	}

	public function test_order_refund_create_refunds_a_woopayments_order_through_its_gateway(): void {
		$order = $this->create_woopayments_order( 'pi_paid', 'succeeded', 'ch_paid' );
		$this->mock_gateway_api_client();

		$result = wp_get_ability( 'woocommerce/order-refund-create' )->execute(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'reason'         => 'POC',
				'refund_payment' => true,
			)
		);

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );
		$this->assertCount( 1, $this->sent_refunds, 'WooPayments should send one Refund_Charge request.' );
		$this->assertSame( 'ch_paid', $this->sent_refunds[0]->get_param( 'charge' ) );
		$this->assertSame( 1000, $this->sent_refunds[0]->get_param( 'amount' ) );
		$this->assertTrue( $result['refund']['refunded_payment'] );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 're_poc', $order->get_refunds()[0]->get_meta( '_wcpay_refund_id' ) );
	}

	public function test_order_refund_create_returns_the_woopayments_refusal_for_an_uncaptured_order(): void {
		$order = $this->create_woopayments_order( 'pi_auth', 'requires_capture', 'ch_auth' );

		$result = wp_get_ability( 'woocommerce/order-refund-create' )->execute(
			array(
				'id'             => $order->get_id(),
				'amount'         => '10',
				'refund_payment' => true,
			)
		);

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'not captured yet', $result->get_error_message() );
		$this->assertCount( 0, $this->sent_refunds );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	public function test_orders_query_filters_match_what_the_woopayments_order_service_wrote(): void {
		$authorized = $this->create_woopayments_order( 'pi_auth', 'requires_capture', 'ch_auth' );
		$blocked    = $this->create_woopayments_order( 'pi_block', 'requires_payment_method', null, OrderStatus::PENDING );
		\WC_Payments::get_order_service()->mark_order_blocked_for_fraud( $blocked, 'pi_block', 'requires_payment_method' );
		$this->create_woopayments_order( 'pi_paid', 'succeeded', 'ch_paid' );

		$ids = function ( array $filter ): array {
			$request = new \WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/woocommerce/orders-query/run' );
			$request->set_query_params(
				array(
					'input' => array(
						'status'  => array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ),
						'filters' => array( 'extensions' => array( 'woocommerce-payments' => $filter ) ),
					),
				)
			);
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
			return array_column( $response->get_data()['orders'], 'id' );
		};

		$this->assertSame( 'block', \WC_Payments::get_order_service()->get_fraud_outcome_status_for_order( wc_get_order( $blocked->get_id() ) ) );
		$this->assertSame( array( $blocked->get_id() ), $ids( array( 'fraud_outcome_status' => 'block' ) ) );
		$this->assertSame( array( $authorized->get_id() ), $ids( array( 'intention_status' => 'requires_capture' ) ) );
	}

	/**
	 * The gateway reads the payment method type from the API client before it refunds. Answer with a card.
	 */
	private function mock_gateway_api_client(): void {
		$gateway = WC()->payment_gateways()->payment_gateways()['woocommerce_payments'];
		$client  = $this->createMock( \WC_Payments_API_Client::class );
		$client->method( 'get_payment_method' )->willReturn( array( 'type' => 'card' ) );
		$property = new \ReflectionProperty( $gateway, 'payments_api_client' );
		$property->setAccessible( true );
		$original = $property->getValue( $gateway );
		$property->setValue( $gateway, $client );
		$this->restore_api_client = static fn() => $property->setValue( $gateway, $original );
	}

	/**
	 * An order paid with WooPayments, with the meta that WooPayments attaches from the payment intent.
	 */
	private function create_woopayments_order( string $intent_id, string $intent_status, ?string $charge_id, string $status = OrderStatus::PROCESSING ): \WC_Order {
		$order = \WC_Helper_Order::create_order();
		$order->set_payment_method( 'woocommerce_payments' );
		$order->set_status( $status );
		$order->save();
		\WC_Payments::get_order_service()->attach_intent_info_to_order__legacy( $order, $intent_id, $intent_status, 'pm_poc', 'cus_poc', $charge_id, 'USD' );
		return wc_get_order( $order->get_id() );
	}

	private function reset_registries(): void {
		global $wp_actions;
		foreach ( array( 'fields', 'validators', 'syncs', 'filters', 'resources' ) as $property ) {
			$reflection = new \ReflectionProperty( ExtensibleAbilities::class, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}
		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_extensible_abilities_resources_init'], $wp_actions['woocommerce_extensible_abilities_init'] );
	}
}
