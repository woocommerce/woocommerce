<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for the WC_Report_Customer_List class.
 */
class WC_Report_Customer_List_Test extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Whether HPOS was enabled before the test.
	 *
	 * @var bool
	 */
	private $previous_hpos_state;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::setup_cot_tables();
		require_once WC_ABSPATH . 'includes/admin/reports/class-wc-report-customer-list.php';
	}

	/**
	 * Allow switching the order storage in each test.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Restore the order storage.
	 */
	public function tearDown(): void {
		try {
			$this->toggle_cot_feature_and_usage( $this->previous_hpos_state );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox The "Last order" column shows the newest paid order, also when order status labels are translated.
	 *
	 * @dataProvider order_storage_provider
	 * @param bool $hpos_enabled Whether HPOS is enabled.
	 */
	public function test_last_order_column_shows_newest_paid_order( bool $hpos_enabled ): void {
		$this->toggle_cot_feature_and_usage( $hpos_enabled );
		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$paid_order  = $this->create_order( OrderStatus::COMPLETED, strtotime( '2023-06-01 10:00:00' ), $customer_id );
		$this->create_order( OrderStatus::PENDING, strtotime( '2023-06-01 12:00:00' ), $customer_id );

		add_filter(
			'wc_order_statuses',
			function ( $statuses ) {
				return array_map(
					function ( $label ) {
						return 'Traduit ' . $label;
					},
					$statuses
				);
			}
		);

		$column = ( new WC_Report_Customer_List() )->column_default( get_user_by( 'id', $customer_id ), 'last_order' );

		$this->assertStringContainsString( '#' . $paid_order->get_order_number() . '<', $column, 'The newest paid order should be shown, not a newer unpaid one.' );
	}

	/**
	 * @testdox The "Link previous orders" action is shown for unlinked guest orders of any status.
	 *
	 * @dataProvider order_storage_provider
	 * @param bool $hpos_enabled Whether HPOS is enabled.
	 */
	public function test_link_previous_orders_action_shown_for_unpaid_guest_order( bool $hpos_enabled ): void {
		$this->toggle_cot_feature_and_usage( $hpos_enabled );
		$customer_id = $this->factory->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'guest-buyer@example.com',
			)
		);
		$guest_order = $this->create_order( OrderStatus::ON_HOLD, strtotime( '2023-06-01 10:00:00' ), 0 );
		$guest_order->set_billing_email( 'guest-buyer@example.com' );
		$guest_order->save();

		$column = ( new WC_Report_Customer_List() )->column_default( get_user_by( 'id', $customer_id ), 'wc_actions' );

		$this->assertStringContainsString( 'link_orders', $column, 'Guest orders are linked regardless of status, so the action should be offered for an unpaid one.' );
	}

	/**
	 * Provides order storage configurations.
	 *
	 * @return array<string, array{bool}>
	 */
	public function order_storage_provider(): array {
		return array(
			'HPOS'       => array( true ),
			'legacy CPT' => array( false ),
		);
	}

	/**
	 * Creates an order with the given status, creation date and customer.
	 *
	 * @param string $status      Order status.
	 * @param int    $created     Creation timestamp.
	 * @param int    $customer_id Customer ID, 0 for a guest order.
	 * @return WC_Order
	 */
	private function create_order( string $status, int $created, int $customer_id ): WC_Order {
		$order = new WC_Order();
		$order->set_status( $status );
		$order->set_customer_id( $customer_id );
		$order->set_date_created( $created );
		$order->save();

		return $order;
	}
}
