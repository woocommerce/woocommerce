<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\ReceiptRendering;

use Automattic\WooCommerce\Internal\ReceiptRendering\ReceiptRenderingEngine;
use Automattic\WooCommerce\Internal\ReceiptRendering\ReceiptRenderingRestController;
use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;
use WC_Helper_Order;
use WC_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTest_Factory;

/**
 * ReceiptRenderingRestController API controller test.
 *
 * @class ReceiptRenderingRestController
 */
class ReceiptRenderingRestControllerTest extends WC_Unit_Test_Case {

	/**
	 * REST server used to dispatch receipt requests.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * User fixture IDs keyed by role.
	 *
	 * @var int[]
	 */
	private static $fixture_user = array();

	/**
	 * @var int[] Associative array of user IDs.
	 */
	private $user = array();

	/**
	 * Name of the receipt file a test created on disk, deleted in tearDown since the database rollback doesn't cover files.
	 *
	 * @var string|null
	 */
	private $receipt_filename = null;

	/**
	 * Create immutable users shared by the test class.
	 *
	 * @param WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$fixture_user['shop_manager'] = $factory->user->create( array( 'role' => 'shop_manager' ) );
		self::$fixture_user['customer']     = $factory->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$controller   = wc_get_container()->get( ReceiptRenderingRestController::class );
		$this->server = $this->create_rest_server_with_routes(
			array( array( $controller, 'register_routes' ) ),
			true
		);

		$this->user = self::$fixture_user;
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		$this->clear_rest_server();
		unset( $this->server );

		if ( $this->receipt_filename ) {
			wc_get_container()->get( TransientFilesEngine::class )->delete_transient_file( $this->receipt_filename );
			$this->receipt_filename = null;
		}

		parent::tearDown();
	}

	/**
	 * Record a user as the author of an order's underlying post.
	 *
	 * @param int $order_id The order ID.
	 * @param int $user_id  The user to record as the post author.
	 */
	private function set_order_post_author( int $order_id, int $user_id ): void {
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_author' => $user_id ), array( 'ID' => $order_id ) );
		clean_post_cache( $order_id );
	}

	/**
	 * A customer who authored the order's post must be refused on both receipt routes.
	 */
	public function test_order_author_customer_is_denied_on_receipt_routes() {
		$order = WC_Helper_Order::create_order();
		$this->set_order_post_author( $order->get_id(), $this->user['customer'] );

		wp_set_current_user( $this->user['customer'] );
		$this->assertTrue(
			current_user_can( 'read_shop_order', $order->get_id() ), // phpcs:ignore WordPress.WP.Capabilities.Unknown -- shop_order meta cap, not in the sniff's core list.
			'Fixture must model an order the author can read.'
		);

		foreach ( array( 'GET', 'POST' ) as $method ) {
			$response = $this->server->dispatch( new WP_REST_Request( $method, '/wc/v3/orders/' . $order->get_id() . '/receipt' ) );
			$this->assertEquals( 403, $response->get_status(), "$method receipt should be forbidden for the order author." );
		}
	}

	/**
	 * A shop manager passes the receipt permission check on both routes.
	 *
	 * GET on an order with no receipt yet returns 404 (past the gate, not 403); POST returns the created receipt.
	 */
	public function test_shop_manager_passes_receipt_permission() {
		$order = WC_Helper_Order::create_order();

		wp_set_current_user( $this->user['shop_manager'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() . '/receipt' ) );
		$this->assertEquals( 404, $response->get_status() );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/receipt' ) );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'receipt_url', $response->get_data() );

		$this->receipt_filename = wc_get_order( $order->get_id() )->get_meta( ReceiptRenderingEngine::RECEIPT_FILE_NAME_META_KEY );
		$this->assertNotEmpty( $this->receipt_filename );
	}
}
