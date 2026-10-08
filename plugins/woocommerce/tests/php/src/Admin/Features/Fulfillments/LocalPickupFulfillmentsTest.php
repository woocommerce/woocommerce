<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Admin\Features\Fulfillments\OrderFulfillmentsRestController;
use WC_Helper_Order;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_REST_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;

/**
 * Tests for local pickup in order fulfillments: the Ready for pickup and Picked up statuses, the
 * pickup location, and the Ready for pickup notification.
 */
class LocalPickupFulfillmentsTest extends WC_REST_Unit_Test_Case {

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Notification actions fired during a test, keyed by hook name.
	 *
	 * @var array<string,int>
	 */
	private array $notifications = array();

	/**
	 * Turn the feature on for the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Restore the feature flag.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Register the routes and count notifications.
	 */
	public function setUp(): void {
		parent::setUp();
		( new OrderFulfillmentsRestController() )->register_routes();
		wp_set_current_user( 1 );
		$this->notifications = array();
		foreach ( array( 'created', 'updated', 'ready_for_pickup' ) as $type ) {
			$hook = "woocommerce_fulfillment_{$type}_notification";
			add_action(
				$hook,
				function () use ( $hook ) {
					$this->notifications[ $hook ] = ( $this->notifications[ $hook ] ?? 0 ) + 1;
				}
			);
		}
	}

	/**
	 * @testdox Ready for pickup is not fulfilled and Picked up is, in both status lists.
	 */
	public function test_pickup_statuses_are_registered(): void {
		$statuses = FulfillmentUtils::get_fulfillment_statuses();
		$this->assertSame( 'Ready for pickup', $statuses['ready_for_pickup']['label'] );
		$this->assertFalse( $statuses['ready_for_pickup']['is_fulfilled'], 'Ready for pickup means the customer has not collected yet' );
		$this->assertSame( 'Picked up', $statuses['picked_up']['label'] );
		$this->assertTrue( $statuses['picked_up']['is_fulfilled'] );

		$order_statuses = FulfillmentUtils::get_order_fulfillment_statuses();
		$this->assertArrayHasKey( 'ready_for_pickup', $order_statuses );
		$this->assertArrayHasKey( 'picked_up', $order_statuses );
	}

	/**
	 * @testdox The pickup location comes from the order's pickup shipping line.
	 */
	public function test_order_pickup_location(): void {
		$pickup = $this->create_order(
			'pickup_location',
			'Pickup',
			array(
				'pickup_location' => 'Downtown store',
				'pickup_address'  => '123 Main Street, Austin, TX 78701',
				'pickup_details'  => 'Ring the bell.',
			)
		);
		$this->assertSame(
			array(
				'name'    => 'Downtown store',
				'address' => '123 Main Street, Austin, TX 78701',
				'details' => 'Ring the bell.',
			),
			FulfillmentUtils::get_order_pickup_location( $pickup )
		);

		$zone_pickup = $this->create_order( 'local_pickup', 'Collect in store' );
		$this->assertSame( 'Collect in store', FulfillmentUtils::get_order_pickup_location( $zone_pickup )['name'], 'A zone Local pickup method stores no location, so its title stands in' );

		$this->assertNull( FulfillmentUtils::get_order_pickup_location( $this->create_order( 'flat_rate', 'Flat rate' ) ), 'An order that ships has no pickup location' );
	}

	/**
	 * @testdox The order reads Ready for pickup while every item waits, and Picked up once all are collected.
	 */
	public function test_order_fulfillment_status_for_pickup(): void {
		$order = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$ready = $this->create_fulfillment( $order, FulfillmentUtils::STATUS_READY_FOR_PICKUP, $this->all_items( $order ) );
		$this->assertSame( 'ready_for_pickup', FulfillmentUtils::calculate_order_fulfillment_status( $order, array( $ready ) ) );

		$ready->set_status( FulfillmentUtils::STATUS_PICKED_UP );
		$ready->save();
		$this->assertSame( 'picked_up', FulfillmentUtils::calculate_order_fulfillment_status( $order, array( new Fulfillment( $ready->get_id() ) ) ) );
		$this->assertNotEmpty( ( new Fulfillment( $ready->get_id() ) )->get_date_fulfilled(), 'Picked up records the time like any fulfillment' );

		$partial_order = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$items         = $this->all_items( $partial_order );
		$one_short     = array(
			'item_id' => $items[0]['item_id'],
			'qty'     => $items[0]['qty'] - 1,
		);
		$this->assertGreaterThan( 0, $one_short['qty'], 'The fixture order needs a line with more than one unit' );
		$partial = $this->create_fulfillment( $partial_order, FulfillmentUtils::STATUS_READY_FOR_PICKUP, array( $one_short ) );
		$this->assertSame( 'unfulfilled', FulfillmentUtils::calculate_order_fulfillment_status( $partial_order, array( $partial ) ), 'Items not yet ready keep the order unfulfilled' );
	}

	/**
	 * @testdox Creating a Ready for pickup fulfillment sends the pickup notification, not the shipping one.
	 */
	public function test_create_ready_for_pickup_sends_pickup_notification(): void {
		$order    = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$response = $this->send( 'POST', $order, null, FulfillmentUtils::STATUS_READY_FOR_PICKUP, false, true );

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( 'ready_for_pickup', $response->get_data()['status'] );
		$this->assertFalse( $response->get_data()['is_fulfilled'] );
		$this->assertSame( array( 'woocommerce_fulfillment_ready_for_pickup_notification' => 1 ), $this->notifications );
	}

	/**
	 * @testdox Without Notify customer, marking ready sends nothing.
	 */
	public function test_ready_for_pickup_without_notify_sends_nothing(): void {
		$order = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$this->send( 'POST', $order, null, FulfillmentUtils::STATUS_READY_FOR_PICKUP, false, false );

		$this->assertSame( array(), $this->notifications );
	}

	/**
	 * @testdox A draft marked ready sends the pickup notification once, and marking it picked up sends nothing.
	 */
	public function test_update_through_pickup_steps(): void {
		$order = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$draft = $this->create_fulfillment( $order, 'unfulfilled', $this->all_items( $order ) );

		$this->send( 'PUT', $order, $draft, FulfillmentUtils::STATUS_READY_FOR_PICKUP, false, true );
		$this->assertSame( array( 'woocommerce_fulfillment_ready_for_pickup_notification' => 1 ), $this->notifications );

		$this->send( 'PUT', $order, $draft, FulfillmentUtils::STATUS_READY_FOR_PICKUP, false, true );
		$this->assertSame( array( 'woocommerce_fulfillment_ready_for_pickup_notification' => 1 ), $this->notifications, 'Saving a fulfillment that is already ready does not send it again' );

		$response = $this->send( 'PUT', $order, $draft, FulfillmentUtils::STATUS_PICKED_UP, true, true );
		$this->assertSame( 'picked_up', $response->get_data()['status'] );
		$this->assertTrue( $response->get_data()['is_fulfilled'] );
		$this->assertSame( array( 'woocommerce_fulfillment_ready_for_pickup_notification' => 1 ), $this->notifications, 'Picked up sends neither the shipping nor the update email' );
	}

	/**
	 * @testdox A shipment still sends the fulfillment created notification.
	 */
	public function test_shipment_notification_is_unchanged(): void {
		$order = $this->create_order( 'flat_rate', 'Flat rate' );
		$this->send( 'POST', $order, null, 'fulfilled', true, true );

		$this->assertSame( array( 'woocommerce_fulfillment_created_notification' => 1 ), $this->notifications );
	}

	/**
	 * @testdox The Ready for pickup email names the location and lists the items.
	 */
	public function test_ready_for_pickup_email_content(): void {
		$order       = $this->create_order(
			'pickup_location',
			'Pickup',
			array(
				'pickup_location' => 'Downtown store',
				'pickup_address'  => '123 Main Street, Austin, TX 78701',
				'pickup_details'  => 'Ring the bell.',
			)
		);
		$fulfillment = $this->create_fulfillment( $order, FulfillmentUtils::STATUS_READY_FOR_PICKUP, $this->all_items( $order ), true );

		$mailer = WC()->mailer();
		// WC_Emails only hooks the fulfillment details when the feature was on as it was built; an
		// earlier test may have built it with the feature off.
		if ( ! has_action( 'woocommerce_email_fulfillment_details', array( $mailer, 'fulfillment_details' ) ) ) {
			add_action( 'woocommerce_email_fulfillment_details', array( $mailer, 'fulfillment_details' ), 10, 5 );
		}
		$emails = $mailer->get_emails();
		$this->assertArrayHasKey( 'WC_Email_Customer_Fulfillment_Ready_For_Pickup', $emails, 'The email is listed with the other fulfillment emails' );

		$email = $emails['WC_Email_Customer_Fulfillment_Ready_For_Pickup'];
		$sent  = array();
		add_filter(
			'pre_wp_mail',
			function ( $short_circuit, $atts ) use ( &$sent ) {
				$sent[] = $atts;
				return true;
			},
			10,
			2
		);
		$email->trigger( $order->get_id(), $fulfillment, $order );

		$this->assertCount( 1, $sent );
		$this->assertStringContainsString( 'ready for pickup', $sent[0]['subject'] );
		$this->assertStringContainsString( 'Downtown store', $sent[0]['message'] );
		$this->assertStringContainsString( '123 Main Street, Austin, TX 78701', $sent[0]['message'] );
		$this->assertStringContainsString( 'Ring the bell.', $sent[0]['message'] );
		$this->assertStringNotContainsString( 'No tracking information', $sent[0]['message'], 'A pickup is not a shipment' );
		$this->assertStringContainsString( $order->get_items()[ array_key_first( $order->get_items() ) ]->get_name(), $sent[0]['message'] );
	}

	/**
	 * Create an order with one shipping method.
	 *
	 * @param string               $method_id The shipping method ID.
	 * @param string               $title     The shipping method title.
	 * @param array<string,string> $meta      Meta on the shipping line.
	 * @return WC_Order
	 */
	private function create_order( string $method_id, string $title, array $meta = array() ): WC_Order {
		$order = WC_Helper_Order::create_order( 1 );
		foreach ( array_keys( $order->get_items( 'shipping' ) ) as $item_id ) {
			$order->remove_item( $item_id );
		}
		$line = new WC_Order_Item_Shipping();
		$line->set_props(
			array(
				'method_title' => $title,
				'method_id'    => $method_id,
				'total'        => 0,
			)
		);
		foreach ( $meta as $key => $value ) {
			$line->add_meta_data( $key, $value, true );
		}
		$order->add_item( $line );
		$order->set_billing_email( 'buyer@example.com' );
		$order->save();
		return wc_get_order( $order->get_id() );
	}

	/**
	 * Every line item at its full quantity.
	 *
	 * @param WC_Order $order The order.
	 * @return array<int,array{item_id:int,qty:int}>
	 */
	private function all_items( WC_Order $order ): array {
		return array_values(
			array_map(
				fn( $item ) => array(
					'item_id' => $item->get_id(),
					'qty'     => $item->get_quantity(),
				),
				$order->get_items()
			)
		);
	}

	/**
	 * Create a fulfillment for an order.
	 *
	 * @param WC_Order $order         The order.
	 * @param string   $status        The status.
	 * @param array    $items         Items and quantities.
	 * @param bool     $with_location Whether to store the order's pickup location on it.
	 * @return Fulfillment
	 */
	private function create_fulfillment( WC_Order $order, string $status, array $items, bool $with_location = false ): Fulfillment {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->set_status( $status );
		$fulfillment->set_items( $items );
		$location = FulfillmentUtils::get_order_pickup_location( $order );
		if ( $with_location && null !== $location ) {
			$fulfillment->add_meta_data( FulfillmentUtils::PICKUP_LOCATION_META_KEY, $location['name'], true );
			$fulfillment->add_meta_data( FulfillmentUtils::PICKUP_ADDRESS_META_KEY, $location['address'], true );
			$fulfillment->add_meta_data( FulfillmentUtils::PICKUP_DETAILS_META_KEY, $location['details'], true );
		}
		$fulfillment->save();
		return $fulfillment;
	}

	/**
	 * Create or update a fulfillment through the REST API, as the fulfillments panel does.
	 *
	 * @param string           $method       POST to create, PUT to update.
	 * @param WC_Order         $order        The order.
	 * @param Fulfillment|null $fulfillment  The fulfillment to update, or null to create.
	 * @param string           $status       The status to save.
	 * @param bool             $is_fulfilled Whether the status is fulfilled.
	 * @param bool             $notify       The panel's Notify customer toggle.
	 * @return \WP_REST_Response
	 */
	private function send( string $method, WC_Order $order, ?Fulfillment $fulfillment, string $status, bool $is_fulfilled, bool $notify ) {
		$path    = '/wc/v3/orders/' . $order->get_id() . '/fulfillments' . ( $fulfillment ? '/' . $fulfillment->get_id() : '' );
		$request = new WP_REST_Request( $method, $path );
		$request->set_query_params( array( 'notify_customer' => $notify ) );
		$request->set_header( 'content-type', 'application/json' );
		$location = FulfillmentUtils::get_order_pickup_location( $order );
		$meta     = array(
			array(
				'id'    => 0,
				'key'   => '_items',
				'value' => $this->all_items( $order ),
			),
		);
		if ( null !== $location ) {
			$meta[] = array(
				'id'    => 0,
				'key'   => FulfillmentUtils::PICKUP_LOCATION_META_KEY,
				'value' => $location['name'],
			);
		}
		$request->set_body(
			wp_json_encode(
				array(
					'entity_type'  => WC_Order::class,
					'entity_id'    => (string) $order->get_id(),
					'status'       => $status,
					'is_fulfilled' => $is_fulfilled,
					'meta_data'    => $meta,
				)
			)
		);
		return $this->server->dispatch( $request );
	}
}
