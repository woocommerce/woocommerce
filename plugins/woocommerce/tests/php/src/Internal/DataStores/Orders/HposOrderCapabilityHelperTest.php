<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\ListTable;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use WC_Unit_Test_Case;

/**
 * Tests for HposOrderCapabilityHelper.
 */
class HposOrderCapabilityHelperTest extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::setup_cot_tables();
	}

	/**
	 * Custom roles registered during a test.
	 *
	 * @var string[]
	 */
	private array $custom_roles = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		add_filter( 'wc_order_statuses', array( $this, 'add_private_test_order_status' ) );
		register_post_status(
			'wc-private-test',
			array(
				'label'   => 'Private test',
				'private' => true,
			)
		);
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->custom_roles as $role ) {
			remove_role( $role );
		}
		$this->custom_roles = array();

		remove_filter( 'wc_order_statuses', array( $this, 'add_private_test_order_status' ) );
		$this->unregister_private_test_order_status();
		$this->clean_up_cot_setup();
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		parent::tearDown();
	}

	/**
	 * Register a private order status for capability mapping tests.
	 *
	 * @param array<string,string> $statuses Order statuses.
	 * @return array<string,string> Order statuses.
	 */
	public function add_private_test_order_status( array $statuses ): array {
		$statuses['wc-private-test'] = 'Private test';
		return $statuses;
	}

	/**
	 * Unregister the private order status added for capability mapping tests.
	 */
	private function unregister_private_test_order_status(): void {
		global $wp_post_statuses;

		unset( $wp_post_statuses['wc-private-test'] );
	}

	/**
	 * @testdox Shop manager can edit an HPOS order.
	 */
	public function test_shop_manager_can_edit_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'edit_shop_order', $order_id ), 'Shop manager should be able to edit HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can delete an HPOS order.
	 */
	public function test_shop_manager_can_delete_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'delete_shop_order', $order_id ), 'Shop manager should be able to delete HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can read an HPOS order.
	 */
	public function test_shop_manager_can_read_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'read_shop_order', $order_id ), 'Shop manager should be able to read HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox User with order edit caps can edit an HPOS order without generic post caps.
	 */
	public function test_user_with_order_caps_can_edit_order_without_generic_post_caps(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_user_with_caps(
			'hpos_order_editor',
			array(
				'read'                    => true,
				'edit_shop_orders'        => true,
				'edit_others_shop_orders' => true,
			)
		);

		$this->assertFalse( current_user_can( 'edit_others_posts' ), 'Test role should not have generic post edit caps' );
		$this->assertTrue( current_user_can( 'edit_shop_order', $order_id ), 'Order-specific edit caps should allow editing HPOS orders' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox REST order edit permission uses order caps for HPOS orders.
	 */
	public function test_rest_edit_permission_uses_order_caps_for_hpos_orders(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_user_with_caps(
			'hpos_rest_order_editor',
			array(
				'read'                    => true,
				'edit_shop_orders'        => true,
				'edit_others_shop_orders' => true,
			)
		);

		$this->assertFalse( current_user_can( 'edit_others_posts' ), 'Test role should not have generic post edit caps' );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'edit', $order_id ), 'REST edit permission should use order caps for HPOS orders' );
	}

	/**
	 * @testdox REST order edit permission rejects users without order edit caps.
	 */
	public function test_rest_edit_permission_rejects_user_without_order_edit_caps(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'edit', $order_id ), 'REST edit permission should reject users without order edit caps' );
	}

	/**
	 * @testdox List table renders a checkbox for an editable HPOS order.
	 */
	public function test_list_table_renders_checkbox_for_editable_hpos_order(): void {
		$order = OrderHelper::create_order();
		$this->login_as_role( 'shop_manager' );

		$list_table    = new ListTable();
		$set_post_type = function () {
			$this->wp_post_type = get_post_type_object( 'shop_order' );
		};
		$set_post_type->call( $list_table );

		$output = $list_table->column_cb( $order );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'cb-select-' . $order->get_id(), $output );
	}

	/**
	 * @testdox Subscriber cannot edit an HPOS order.
	 */
	public function test_subscriber_cannot_edit_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( current_user_can( 'edit_shop_order', $order_id ), 'Subscriber should not be able to edit HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Subscriber cannot delete an HPOS order.
	 */
	public function test_subscriber_cannot_delete_order(): void {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( current_user_can( 'delete_shop_order', $order_id ), 'Subscriber should not be able to delete HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox User without private order edit cap cannot edit private HPOS order.
	 */
	public function test_user_without_private_order_edit_cap_cannot_edit_private_order(): void {
		$order    = $this->create_private_order();
		$order_id = $order->get_id();

		$this->login_as_user_with_caps(
			'hpos_private_order_editor_without_private_cap',
			array(
				'read'                    => true,
				'edit_shop_orders'        => true,
				'edit_others_shop_orders' => true,
			)
		);

		$this->assertFalse( current_user_can( 'edit_shop_order', $order_id ), 'Private HPOS orders should require edit_private_shop_orders' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox User without private order delete cap cannot delete private HPOS order.
	 */
	public function test_user_without_private_order_delete_cap_cannot_delete_private_order(): void {
		$order    = $this->create_private_order();
		$order_id = $order->get_id();

		$this->login_as_user_with_caps(
			'hpos_private_order_deleter_without_private_cap',
			array(
				'read'                      => true,
				'delete_shop_orders'        => true,
				'delete_others_shop_orders' => true,
			)
		);

		$this->assertFalse( current_user_can( 'delete_shop_order', $order_id ), 'Private HPOS orders should require delete_private_shop_orders' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox User with private order read cap can read private HPOS order.
	 */
	public function test_user_with_private_order_read_cap_can_read_private_order(): void {
		$order    = $this->create_private_order();
		$order_id = $order->get_id();

		$this->login_as_user_with_caps(
			'hpos_private_order_reader',
			array(
				'read'                     => true,
				'read_private_shop_orders' => true,
			)
		);

		$this->assertTrue( current_user_can( 'read_shop_order', $order_id ), 'Private HPOS orders should allow read_private_shop_orders' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Subscriber cannot read private HPOS order.
	 */
	public function test_subscriber_cannot_read_private_order(): void {
		$order    = $this->create_private_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( current_user_can( 'read_shop_order', $order_id ), 'Subscriber should not be able to read private HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can edit a refund for an HPOS order.
	 */
	public function test_shop_manager_can_edit_refund(): void {
		$this->login_as_role( 'shop_manager' );

		$order  = OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
				'reason'   => 'Test refund',
			)
		);

		$this->assertTrue( current_user_can( 'edit_shop_order', $refund->get_id() ), 'Shop manager should be able to edit refund for HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Shop manager can delete a refund for an HPOS order.
	 */
	public function test_shop_manager_can_delete_refund(): void {
		$this->login_as_role( 'shop_manager' );

		$order  = OrderHelper::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
				'reason'   => 'Test refund',
			)
		);

		$this->assertTrue( current_user_can( 'delete_shop_order', $refund->get_id() ), 'Shop manager should be able to delete refund for HPOS order' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Cap translation does not apply when HPOS is disabled.
	 */
	public function test_filter_does_not_apply_when_hpos_disabled(): void {
		$this->toggle_cot_feature_and_usage( false );

		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'edit_shop_order', $order_id ), 'Shop manager should still be able to edit orders when HPOS is off' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Cap translation does not apply when sync is enabled.
	 */
	public function test_filter_does_not_apply_when_sync_enabled(): void {
		$this->enable_cot_sync();

		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->login_as_role( 'shop_manager' );

		$this->assertTrue( current_user_can( 'edit_shop_order', $order_id ), 'Shop manager should be able to edit orders when sync is on' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * @testdox Cap translation does not affect non-order posts.
	 */
	public function test_filter_does_not_affect_regular_posts(): void {
		$post_id = $this->factory->post->create();

		$this->login_as_role( 'subscriber' );

		$this->assertFalse( current_user_can( 'edit_post', $post_id ), 'Subscriber should not be able to edit regular posts even with HPOS cap translation active' );
	}

	/**
	 * Create a user with a custom role and set it as current.
	 *
	 * @param string             $role Role name.
	 * @param array<string,bool> $caps Capabilities.
	 * @return int User ID.
	 */
	private function login_as_user_with_caps( string $role, array $caps ): int {
		remove_role( $role );
		add_role( $role, $role, $caps );
		$this->custom_roles[] = $role;

		return $this->login_as_role( $role );
	}

	/**
	 * Create an HPOS order using the private test status.
	 *
	 * @return \WC_Order Order object.
	 */
	private function create_private_order(): \WC_Order {
		return \WC_Helper_Order::create_order(
			1,
			null,
			array(
				'status' => 'private-test',
			)
		);
	}
}
