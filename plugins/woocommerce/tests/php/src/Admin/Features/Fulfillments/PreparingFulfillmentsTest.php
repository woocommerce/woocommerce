<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsRenderer;
use Automattic\WooCommerce\Admin\Features\Fulfillments\OrderFulfillmentsRestController;
use WC_Helper_Order;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_REST_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;

/**
 * Tests for the Preparing fulfillment status: the status itself, the order badge, the Preparing
 * notification, the status transition hook, and the customer-facing progress steps.
 */
class PreparingFulfillmentsTest extends WC_REST_Unit_Test_Case {

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
	 * Status transitions announced during a test, as [ previous, next ] pairs.
	 *
	 * @var array<int,array{0:?string,1:string}>
	 */
	private array $transitions = array();

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
	 * Register the routes and record notifications and transitions.
	 */
	public function setUp(): void {
		parent::setUp();
		( new OrderFulfillmentsRestController() )->register_routes();
		wp_set_current_user( 1 );
		$this->notifications = array();
		$this->transitions   = array();
		foreach ( array( 'created', 'updated', 'ready_for_pickup', 'preparing' ) as $type ) {
			$hook = "woocommerce_fulfillment_{$type}_notification";
			add_action(
				$hook,
				function () use ( $hook ) {
					$this->notifications[ $hook ] = ( $this->notifications[ $hook ] ?? 0 ) + 1;
				}
			);
		}
		add_action(
			'woocommerce_fulfillment_status_transition',
			function ( $order_id, $fulfillment, $previous, $next ) {
				$this->transitions[] = array( $previous, $next );
			},
			10,
			4
		);
	}

	/**
	 * @testdox Preparing is a registered fulfillment and order fulfillment status, and is not fulfilled.
	 */
	public function test_preparing_status_is_registered(): void {
		$statuses = FulfillmentUtils::get_fulfillment_statuses();
		$this->assertSame( 'Preparing', $statuses['preparing']['label'] );
		$this->assertFalse( $statuses['preparing']['is_fulfilled'], 'Preparing happens before anything is handed over' );
		$this->assertArrayHasKey( 'preparing', FulfillmentUtils::get_order_fulfillment_statuses() );
	}

	/**
	 * @testdox The order reads Preparing while work is under way, and moves on as soon as anything is fulfilled.
	 */
	public function test_order_fulfillment_status_while_preparing(): void {
		$order = $this->create_order( 'flat_rate', 'Flat rate' );
		$items = $this->all_items( $order );
		$this->assertGreaterThan( 1, $items[0]['qty'], 'The fixture order needs a line with more than one unit' );
		$most_of_it = array(
			'item_id' => $items[0]['item_id'],
			'qty'     => $items[0]['qty'] - 1,
		);
		$the_rest   = array(
			'item_id' => $items[0]['item_id'],
			'qty'     => 1,
		);

		$first = $this->create_fulfillment( $order, FulfillmentUtils::STATUS_PREPARING, array( $most_of_it ) );
		$this->assertSame( 'preparing', FulfillmentUtils::calculate_order_fulfillment_status( $order, array( $first ) ), 'One part preparing with the rest pending still reads Preparing' );

		$second = $this->create_fulfillment( $order, 'unfulfilled', array( $the_rest ) );
		$this->assertSame( 'preparing', FulfillmentUtils::calculate_order_fulfillment_status( $order, array( $first, $second ) ), 'A draft next to a preparing fulfillment does not hide the progress' );

		$second->set_status( 'fulfilled' );
		$second->save();
		$this->assertSame( 'partially_fulfilled', FulfillmentUtils::calculate_order_fulfillment_status( $order, array( $first, new Fulfillment( $second->get_id() ) ) ), 'Anything fulfilled takes precedence over preparing' );

		$pickup = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$ready  = $this->create_fulfillment( $pickup, FulfillmentUtils::STATUS_READY_FOR_PICKUP, $this->all_items( $pickup ) );
		$this->assertSame( 'ready_for_pickup', FulfillmentUtils::calculate_order_fulfillment_status( $pickup, array( $ready ) ), 'Ready for pickup is unchanged' );
	}

	/**
	 * @testdox Creating a Preparing fulfillment sends the Preparing notification only.
	 */
	public function test_create_preparing_sends_preparing_notification(): void {
		$order    = $this->create_order( 'flat_rate', 'Flat rate' );
		$response = $this->send( 'POST', $order, null, FulfillmentUtils::STATUS_PREPARING, false, true );

		$this->assertSame( WP_Http::CREATED, $response->get_status() );
		$this->assertSame( 'preparing', $response->get_data()['status'] );
		$this->assertFalse( $response->get_data()['is_fulfilled'] );
		$this->assertSame( array( 'woocommerce_fulfillment_preparing_notification' => 1 ), $this->notifications );
		$this->assertSame( array( array( null, 'preparing' ) ), $this->transitions, 'A new fulfillment announces its first status with no previous one' );
	}

	/**
	 * @testdox Without Notify customer, starting to prepare sends nothing but still announces the transition.
	 */
	public function test_preparing_without_notify_sends_nothing(): void {
		$order = $this->create_order( 'flat_rate', 'Flat rate' );
		$this->send( 'POST', $order, null, FulfillmentUtils::STATUS_PREPARING, false, false );

		$this->assertSame( array(), $this->notifications );
		$this->assertSame( array( array( null, 'preparing' ) ), $this->transitions );
	}

	/**
	 * @testdox A shipment goes draft, preparing, fulfilled: one Preparing email, then one shipping email, with every step announced.
	 */
	public function test_shipping_order_through_preparing(): void {
		$order = $this->create_order( 'flat_rate', 'Flat rate' );
		$draft = $this->create_fulfillment( $order, 'unfulfilled', $this->all_items( $order ) );

		$this->send( 'PUT', $order, $draft, FulfillmentUtils::STATUS_PREPARING, false, true );
		$this->assertSame( array( 'woocommerce_fulfillment_preparing_notification' => 1 ), $this->notifications );

		$this->send( 'PUT', $order, $draft, FulfillmentUtils::STATUS_PREPARING, false, true );
		$this->assertSame( array( 'woocommerce_fulfillment_preparing_notification' => 1 ), $this->notifications, 'Saving a fulfillment that is already preparing does not send it again' );
		$this->assertSame( 1, count( $this->transitions ), 'Saving without a status change announces nothing' );

		$response = $this->send( 'PUT', $order, $draft, 'fulfilled', true, true );
		$this->assertTrue( $response->get_data()['is_fulfilled'] );
		$this->assertSame(
			array(
				'woocommerce_fulfillment_preparing_notification' => 1,
				'woocommerce_fulfillment_created_notification'   => 1,
			),
			$this->notifications,
			'Fulfilling after preparing sends the usual shipping email'
		);
		$this->assertSame(
			array(
				array( 'unfulfilled', 'preparing' ),
				array( 'preparing', 'fulfilled' ),
			),
			$this->transitions
		);
	}

	/**
	 * @testdox A pickup order goes preparing, ready, picked up: Preparing and Ready for pickup emails only.
	 */
	public function test_pickup_order_through_preparing(): void {
		$order = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );

		$response    = $this->send( 'POST', $order, null, FulfillmentUtils::STATUS_PREPARING, false, true );
		$fulfillment = new Fulfillment( $response->get_data()['id'] );
		$this->send( 'PUT', $order, $fulfillment, FulfillmentUtils::STATUS_READY_FOR_PICKUP, false, true );
		$this->send( 'PUT', $order, $fulfillment, FulfillmentUtils::STATUS_PICKED_UP, true, true );

		$this->assertSame(
			array(
				'woocommerce_fulfillment_preparing_notification'        => 1,
				'woocommerce_fulfillment_ready_for_pickup_notification' => 1,
			),
			$this->notifications
		);
		$this->assertSame(
			array(
				array( null, 'preparing' ),
				array( 'preparing', 'ready_for_pickup' ),
				array( 'ready_for_pickup', 'picked_up' ),
			),
			$this->transitions
		);
	}

	/**
	 * @testdox The Preparing email lists the items, promises tracking later, and never mentions missing tracking.
	 */
	public function test_preparing_email_content(): void {
		$order       = $this->create_order( 'flat_rate', 'Flat rate' );
		$fulfillment = $this->create_fulfillment( $order, FulfillmentUtils::STATUS_PREPARING, $this->all_items( $order ) );

		$mailer = WC()->mailer();
		// An earlier test may have built the mailer with the feature off, so it would lack the fulfillment
		// emails and hooks. Rebuild it now that the feature is on.
		if ( ! array_key_exists( 'WC_Email_Customer_Fulfillment_Preparing', $mailer->get_emails() ) ) {
			$mailer->init();
		}
		if ( ! has_action( 'woocommerce_email_fulfillment_details', array( $mailer, 'fulfillment_details' ) ) ) {
			add_action( 'woocommerce_email_fulfillment_details', array( $mailer, 'fulfillment_details' ), 10, 5 );
		}
		$emails = $mailer->get_emails();
		$this->assertArrayHasKey( 'WC_Email_Customer_Fulfillment_Preparing', $emails, 'The email is listed with the other fulfillment emails' );

		$email = $emails['WC_Email_Customer_Fulfillment_Preparing'];
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
		$this->assertStringContainsString( 'preparing your', $sent[0]['subject'] );
		$this->assertStringContainsString( 'started preparing', $sent[0]['message'] );
		$this->assertStringContainsString( 'send tracking details once these items ship', $sent[0]['message'] );
		$this->assertStringNotContainsString( 'No tracking information', $sent[0]['message'], 'Nothing has shipped yet, so missing tracking is not news' );
		$this->assertStringContainsString( $order->get_items()[ array_key_first( $order->get_items() ) ]->get_name(), $sent[0]['message'] );
	}

	/**
	 * @testdox The progress steps follow the order: received, preparing, shipped or ready for pickup, delivered or picked up.
	 */
	public function test_progress_steps(): void {
		$order = $this->create_order( 'flat_rate', 'Flat rate' );
		$this->assertSame(
			array(
				'received'  => 'current',
				'preparing' => 'upcoming',
				'shipped'   => 'upcoming',
				'delivered' => 'upcoming',
			),
			$this->states( FulfillmentUtils::get_order_progress_steps( $order, array() ) ),
			'A processing order with no fulfillments has just been received'
		);

		$fulfillment = $this->create_fulfillment( $order, FulfillmentUtils::STATUS_PREPARING, $this->all_items( $order ) );
		$this->assertSame(
			array(
				'received'  => 'done',
				'preparing' => 'current',
				'shipped'   => 'upcoming',
				'delivered' => 'upcoming',
			),
			$this->states( FulfillmentUtils::get_order_progress_steps( $order, array( $fulfillment ) ) )
		);

		$fulfillment->set_status( 'fulfilled' );
		$fulfillment->save();
		$fulfillment = new Fulfillment( $fulfillment->get_id() );
		$this->assertSame(
			array(
				'received'  => 'done',
				'preparing' => 'done',
				'shipped'   => 'current',
				'delivered' => 'upcoming',
			),
			$this->states( FulfillmentUtils::get_order_progress_steps( $order, array( $fulfillment ) ) ),
			'Shipped stays current until the order is completed, because delivery is the carrier\'s to confirm'
		);

		$order->set_status( 'completed' );
		$order->save();
		$this->assertSame(
			array(
				'received'  => 'done',
				'preparing' => 'done',
				'shipped'   => 'done',
				'delivered' => 'done',
			),
			$this->states( FulfillmentUtils::get_order_progress_steps( wc_get_order( $order->get_id() ), array( $fulfillment ) ) ),
			'Completing the order, by hand or through an integration, finishes the strip'
		);

		$pickup = $this->create_order( 'pickup_location', 'Pickup', array( 'pickup_location' => 'Downtown store' ) );
		$ready  = $this->create_fulfillment( $pickup, FulfillmentUtils::STATUS_READY_FOR_PICKUP, $this->all_items( $pickup ) );
		$this->assertSame(
			array(
				'received'         => 'done',
				'preparing'        => 'done',
				'ready_for_pickup' => 'current',
				'picked_up'        => 'upcoming',
			),
			$this->states( FulfillmentUtils::get_order_progress_steps( $pickup, array( $ready ) ) ),
			'A pickup order uses the pickup wording'
		);

		$ready->set_status( FulfillmentUtils::STATUS_PICKED_UP );
		$ready->save();
		$this->assertSame(
			array(
				'received'         => 'done',
				'preparing'        => 'done',
				'ready_for_pickup' => 'done',
				'picked_up'        => 'done',
			),
			$this->states( FulfillmentUtils::get_order_progress_steps( $pickup, array( new Fulfillment( $ready->get_id() ) ) ) )
		);

		$cancelled = $this->create_order( 'flat_rate', 'Flat rate' );
		$cancelled->set_status( 'cancelled' );
		$cancelled->save();
		$this->assertSame( array(), FulfillmentUtils::get_order_progress_steps( wc_get_order( $cancelled->get_id() ), array() ), 'A cancelled order shows no progress' );
	}

	/**
	 * @testdox The progress strip renders above the order table with the current step marked.
	 */
	public function test_progress_strip_renders(): void {
		$order       = $this->create_order( 'flat_rate', 'Flat rate' );
		$fulfillment = $this->create_fulfillment( $order, FulfillmentUtils::STATUS_PREPARING, $this->all_items( $order ) );
		$this->assertNotEmpty( $fulfillment->get_id() );

		$renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		ob_start();
		$renderer->render_fulfillment_progress( wc_get_order( $order->get_id() ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'woocommerce-fulfillment-progress__step--current woocommerce-fulfillment-progress__step--preparing', $html );
		$this->assertStringContainsString( 'aria-current="step"', $html );
		$this->assertStringContainsString( 'Current status: <strong>Preparing</strong>', $html );
		$this->assertStringContainsString( 'Shipped', $html );
		$this->assertStringNotContainsString( 'Ready for pickup', $html, 'A shipping order uses the shipping wording' );
	}

	/**
	 * Map steps to key => state.
	 *
	 * @param array $steps The steps.
	 * @return array<string,string>
	 */
	private function states( array $steps ): array {
		$states = array();
		foreach ( $steps as $step ) {
			$states[ $step['key'] ] = $step['state'];
		}
		return $states;
	}

	/**
	 * Create a processing order with one shipping method.
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
		$order->set_status( 'processing' );
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
	 * @param WC_Order $order  The order.
	 * @param string   $status The status.
	 * @param array    $items  Items and quantities.
	 * @return Fulfillment
	 */
	private function create_fulfillment( WC_Order $order, string $status, array $items ): Fulfillment {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->set_status( $status );
		$fulfillment->set_items( $items );
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
