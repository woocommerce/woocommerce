<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Orders\ListTable;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests related to order list table in admin.
 */
class ListTableTest extends \WC_Unit_Test_Case {

	/**
	 * Message of the exception used to trap the terminal redirect in handle_bulk_actions().
	 */
	private const REDIRECT_SENTINEL = 'woocommerce_list_table_test_redirected';

	/**
	 * @var ListTable
	 */
	private $sut;

	/**
	 * Previous HPOS state.
	 *
	 * @var bool
	 */
	private static bool $hpos_prev_state;

	/**
	 * Custom roles registered during a test.
	 *
	 * @var string[]
	 */
	private array $custom_roles = array();

	/**
	 * Set up class fixtures.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$hpos_prev_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		OrderHelper::create_order_custom_table_if_not_exist();

		if ( ! self::$hpos_prev_state ) {
			OrderHelper::toggle_cot_feature_and_usage( true );
		}
	}

	/**
	 * Tear down class fixtures.
	 */
	public static function tearDownAfterClass(): void {
		self::clear_hpos_orders();

		if ( OrderUtil::custom_orders_table_usage_is_enabled() !== self::$hpos_prev_state ) {
			OrderHelper::toggle_cot_feature_and_usage( self::$hpos_prev_state );
		}

		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		parent::tearDownAfterClass();
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut      = new ListTable();
		$set_order_type = function ( $order_type ) {
			$this->order_type = $order_type;
		};
		$set_order_type->call( $this->sut, 'shop_order' );
	}

	/**
	 * Tear down - removes any custom roles registered during a test.
	 */
	public function tearDown(): void {
		foreach ( $this->custom_roles as $role ) {
			remove_role( $role );
		}
		$this->custom_roles = array();

		parent::tearDown();
	}

	/**
	 * Helper method to call protected get_and_maybe_update_months_filter_cache.
	 *
	 * @param ListTable $sut ListTable instance.
	 *
	 * @return array YearMonth Array.
	 */
	public function call_get_months_filter_options( ListTable $sut ) {
		$callable = function () {
			return $this->get_months_filter_options();
		};
		return $callable->call( $sut );
	}

	/**
	 * @testdox The months filter options are filled out for every month between the oldest order and the current month.
	 */
	public function test_get_months_filter_options() {
		$start_date     = new \WC_DateTime( '2020-03-01 00:00:00' );
		$current_date   = new \WC_DateTime();
		$expected_count = $this->get_months_count( $start_date, $current_date );

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( $start_date );
		$order->save();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( $expected_count, $year_months );
		$this->assertEquals( gmdate( 'Y', time() ), $year_months[0]->year );
		$this->assertEquals( gmdate( 'n', time() ), $year_months[0]->month );
		$this->assertEquals( 2020, end( $year_months )->year );
		$this->assertEquals( 3, end( $year_months )->month );
	}

	/**
	 * @testdox The months filter options works as expected when there are no orders.
	 */
	public function test_get_months_filter_options_no_orders() {
		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( 1, $year_months );
		$this->assertEquals( gmdate( 'Y', time() ), $year_months[0]->year );
		$this->assertEquals( gmdate( 'n', time() ), $year_months[0]->month );
	}

	/**
	 * @testdox The available months options don't take into account trashed orders.
	 */
	public function test_get_months_filter_options_skip_trash() {
		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( new \WC_DateTime( '2025-01-02 00:00:00' ) );
		$order->set_status( OrderStatus::TRASH );
		$order->save();

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( new \WC_DateTime( '2025-02-02 00:00:00' ) );
		$order->save();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertEquals( 2025, end( $year_months )->year );
		$this->assertEquals( 2, end( $year_months )->month );
	}

	/**
	 * @testdox The months filter options works as expected with only one month.
	 */
	public function test_get_months_filter_options_single_month() {
		\WC_Helper_Order::create_order();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( 1, $year_months );
		$this->assertEquals( gmdate( 'Y', time() ), $year_months[0]->year );
		$this->assertEquals( gmdate( 'n', time() ), $year_months[0]->month );
	}

	/**
	 * @testdox The available months options are based on the site's timezone, rather than UTC/GMT.
	 */
	public function test_get_months_filter_options_timezone_edge() {
		update_option( 'gmt_offset', '-5' );

		$date  = new \WC_DateTime( '2024-12-31 22:00:00', wp_timezone() ); // 2025-01-01 01:00:00 in UTC.
		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( $date );
		$order->save();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertEquals( 2024, end( $year_months )->year );
		$this->assertEquals( 12, end( $year_months )->month );

		delete_option( 'gmt_offset' );
	}

	/**
	 * @testdox The months filter options works as expected when all orders have a future date.
	 *
	 * When all orders have a future date, the month options range should go from the current date to
	 * the order date farthest in the future.
	 */
	public function test_get_months_filter_options_only_future_orders() {
		$current_date   = new \WC_DateTime( 'now', new \DateTimeZone( 'UTC' ) );
		$start_date     = new \WC_DateTime( '+ 1 years', new \DateTimeZone( 'UTC' ) );
		$end_date       = new \WC_DateTime( '+ 2 years', new \DateTimeZone( 'UTC' ) );
		$expected_count = $this->get_months_count( $current_date, $end_date );

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( $start_date );
		$order->save();

		$order = \WC_Helper_Order::create_order();
		$order->set_date_created( $end_date );
		$order->save();

		$year_months = $this->call_get_months_filter_options( $this->sut );

		$this->assertCount( $expected_count, $year_months );
		$this->assertEquals( $end_date->format( 'Y' ), $year_months[0]->year );
		$this->assertEquals( $end_date->format( 'n' ), $year_months[0]->month );
		$this->assertEquals( gmdate( 'Y', time() ), end( $year_months )->year );
		$this->assertEquals( gmdate( 'n', time() ), end( $year_months )->month );
	}

	/**
	 * Get the total number of year-month items there should be between two dates.
	 *
	 * Note that this is different from calculating the elapsed time between the two dates. For this we instead care
	 * about which year-months from the calendar are present.
	 *
	 * @param \DateTime $start The start of the date range.
	 * @param \DateTime $end   The end of the date range.
	 *
	 * @return int
	 */
	private function get_months_count( \DateTime $start, \DateTime $end ): int {
		$start_year  = (int) $start->format( 'Y' );
		$start_month = (int) $start->format( 'n' );
		$end_year    = (int) $end->format( 'Y' );
		$end_month   = (int) $end->format( 'n' );

		$months_from_years = ( $end_year - $start_year ) * 12;
		$start_month_diff  = $start_month - 1;

		return $months_from_years - $start_month_diff + $end_month;
	}

	/**
	 * @testdox When filtering by created_via, only orders with that specific value should be shown.
	 */
	public function test_filtering_by_created_via_shows_only_matching_orders() {
		$order1 = \WC_Helper_Order::create_order();
		$order1->set_created_via( 'rest-api' );
		$order1->save();

		$order2 = \WC_Helper_Order::create_order();
		$order2->set_created_via( 'pos-rest-api' );
		$order2->save();

		$_GET['_created_via'] = 'rest-api';

		$this->sut->prepare_items();

		$get_items = function () {
			return $this->items;
		};

		$filtered_items = $get_items->call( $this->sut );

		$this->assertCount( 1, $filtered_items ); // Only one order should be shown.
		$this->assertEquals( 'rest-api', $filtered_items[0]->get_created_via() );
		$this->assertEquals( $order1->get_id(), $filtered_items[0]->get_id() );
	}

	/**
	 * @testdox When the created_via filter is empty, all orders should be shown.
	 */
	public function test_filtering_by_created_via_shows_all_orders_when_no_filter() {
		$order1 = \WC_Helper_Order::create_order();
		$order1->set_created_via( 'rest-api' );
		$order1->save();

		$order2 = \WC_Helper_Order::create_order();
		$order2->set_created_via( 'pos-rest-api' );
		$order2->save();

		unset( $_GET['_created_via'] );

		$this->sut->prepare_items();

		$get_items = function () {
			return $this->items;
		};

		$filtered_items = $get_items->call( $this->sut );

		$this->assertCount( 2, $filtered_items ); // Both orders should be shown.
	}

	/**
	 * @testdox Bulk actions include "Move to Trash" when the user can delete orders.
	 */
	public function test_bulk_actions_include_trash_when_user_can_delete_orders(): void {
		$this->login_as_role( 'shop_manager' );

		$actions = $this->call_get_bulk_actions();

		$this->assertArrayHasKey( 'trash', $actions, 'Users who can delete orders should see the Move to Trash bulk action' );
		$this->assertArrayHasKey( 'mark_completed', $actions, 'Status-change bulk actions should remain available' );
	}

	/**
	 * @testdox Bulk actions include "Delete permanently" in the trash when the user can delete orders.
	 */
	public function test_bulk_actions_include_delete_permanently_when_user_can_delete_orders(): void {
		$this->login_as_role( 'shop_manager' );

		$actions = $this->call_get_bulk_actions( array( 'trash' ) );

		$this->assertArrayHasKey( 'delete', $actions, 'Users who can delete orders should see the Delete permanently bulk action' );
		$this->assertArrayHasKey( 'untrash', $actions, 'The Restore bulk action should remain available in the trash' );
	}

	/**
	 * @testdox Bulk actions exclude "Move to Trash" when the user cannot delete orders.
	 */
	public function test_bulk_actions_exclude_trash_when_user_cannot_delete_orders(): void {
		$this->login_as_user_with_caps(
			'orders_editor_without_delete',
			array(
				'read'                    => true,
				'edit_shop_orders'        => true,
				'edit_others_shop_orders' => true,
			)
		);

		$actions = $this->call_get_bulk_actions();

		$this->assertArrayNotHasKey( 'trash', $actions, 'Users without the delete capability should not see the Move to Trash bulk action' );
		$this->assertArrayHasKey( 'mark_completed', $actions, 'Status-change bulk actions should still be available without the delete capability' );
	}

	/**
	 * @testdox Bulk actions exclude "Delete permanently" in the trash when the user cannot delete orders.
	 */
	public function test_bulk_actions_exclude_delete_permanently_when_user_cannot_delete_orders(): void {
		$this->login_as_user_with_caps(
			'orders_editor_without_delete_trash',
			array(
				'read'                    => true,
				'edit_shop_orders'        => true,
				'edit_others_shop_orders' => true,
			)
		);

		$actions = $this->call_get_bulk_actions( array( 'trash' ) );

		$this->assertArrayNotHasKey( 'delete', $actions, 'Users without the delete capability should not see the Delete permanently bulk action' );
		$this->assertArrayHasKey( 'untrash', $actions, 'The Restore bulk action should still be available without the delete capability' );
	}

	/**
	 * @testdox Bulk actions are empty for users without the edit others capability.
	 */
	public function test_bulk_actions_empty_for_users_without_edit_others_capability(): void {
		$this->login_as_role( 'customer' );

		$actions = $this->call_get_bulk_actions();

		$this->assertSame( array(), $actions, 'Users without the edit others capability should not see any bulk actions' );
	}

	/**
	 * Helper to invoke the protected get_bulk_actions() method with a given status view.
	 *
	 * @param string[]|null $status The status filter applied to the list table, or null for the default view.
	 * @return array<string, string> The available bulk actions.
	 */
	private function call_get_bulk_actions( ?array $status = null ): array {
		$closure = function ( $status ) {
			$this->wp_post_type               = get_post_type_object( $this->order_type );
			$this->order_query_args['status'] = $status;

			return $this->get_bulk_actions();
		};

		return $closure->call( $this->sut, $status );
	}

	/**
	 * @testdox handle_bulk_actions() does not trash selected orders for a user without the delete capability.
	 */
	public function test_handle_bulk_actions_trash_blocked_without_delete_capability(): void {
		$order = \WC_Helper_Order::create_order();

		$this->login_as_user_with_caps(
			'orders_editor_without_delete_handler',
			array(
				'read'                    => true,
				'edit_shop_orders'        => true,
				'edit_others_shop_orders' => true,
			)
		);

		$_REQUEST['action']   = 'trash';
		$_REQUEST['id']       = array( $order->get_id() );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'bulk-orders' );

		$this->invoke_handle_bulk_actions();

		unset( $_REQUEST['action'], $_REQUEST['id'], $_REQUEST['_wpnonce'] );

		$this->assertNotSame( 'trash', wc_get_order( $order->get_id() )->get_status(), 'Orders should not be trashed for a user without the delete capability' );
	}

	/**
	 * @testdox handle_bulk_actions() trashes selected orders for a user with the delete capability.
	 */
	public function test_handle_bulk_actions_trash_allowed_with_delete_capability(): void {
		$order = \WC_Helper_Order::create_order();

		$this->login_as_role( 'shop_manager' );

		$_REQUEST['action']   = 'trash';
		$_REQUEST['id']       = array( $order->get_id() );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'bulk-orders' );

		$this->invoke_handle_bulk_actions();

		unset( $_REQUEST['action'], $_REQUEST['id'], $_REQUEST['_wpnonce'] );

		$this->assertSame( 'trash', wc_get_order( $order->get_id() )->get_status(), 'Orders should be trashed for a user with the delete capability' );
	}

	/**
	 * @testdox handle_bulk_actions() blocks trashing for a role that has delete_others but not the base delete capability.
	 */
	public function test_handle_bulk_actions_trash_blocked_without_base_delete_capability(): void {
		$order = \WC_Helper_Order::create_order();

		$this->login_as_user_with_caps(
			'orders_editor_delete_others_only',
			array(
				'read'                      => true,
				'edit_shop_orders'          => true,
				'edit_others_shop_orders'   => true,
				'delete_others_shop_orders' => true,
			)
		);

		$_REQUEST['action']   = 'trash';
		$_REQUEST['id']       = array( $order->get_id() );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'bulk-orders' );

		$this->invoke_handle_bulk_actions();

		unset( $_REQUEST['action'], $_REQUEST['id'], $_REQUEST['_wpnonce'] );

		$this->assertNotSame( 'trash', wc_get_order( $order->get_id() )->get_status(), 'Removing delete_shop_orders should block deletion even when delete_others_shop_orders remains' );
	}

	/**
	 * @testdox handle_bulk_actions() blocks emptying the trash for a user without the delete capability.
	 */
	public function test_handle_bulk_actions_empty_trash_blocked_without_delete_capability(): void {
		$order = \WC_Helper_Order::create_order();

		// Move the order to the trash without permanently deleting it.
		$order->set_status( OrderStatus::TRASH );
		$order->save();

		$this->login_as_user_with_caps(
			'orders_editor_without_delete_empty_trash',
			array(
				'read'                    => true,
				'edit_shop_orders'        => true,
				'edit_others_shop_orders' => true,
			)
		);

		$_REQUEST['delete_all'] = '1';

		$this->invoke_handle_bulk_actions();

		unset( $_REQUEST['delete_all'] );

		$this->assertNotFalse( wc_get_order( $order->get_id() ), 'Trashed orders should not be permanently deleted for a user without the delete capability' );
	}

	/**
	 * @testdox handle_bulk_actions() skips IDs that are not orders of this list table's type.
	 */
	public function test_handle_bulk_actions_trash_skips_non_order_ids(): void {
		$order   = \WC_Helper_Order::create_order();
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Not an order',
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		// An administrator can delete the post above, so only the order type check can filter it out.
		$this->login_as_role( 'administrator' );

		$inject_post_id = static function ( $ids ) use ( $post_id ) {
			$ids[] = $post_id;

			return $ids;
		};
		add_filter( 'woocommerce_bulk_action_ids', $inject_post_id );

		$_REQUEST['action']   = 'trash';
		$_REQUEST['id']       = array( $order->get_id() );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'bulk-orders' );

		try {
			$this->invoke_handle_bulk_actions();
		} finally {
			remove_filter( 'woocommerce_bulk_action_ids', $inject_post_id );
			unset( $_REQUEST['action'], $_REQUEST['id'], $_REQUEST['_wpnonce'] );
		}

		$this->assertSame( 'trash', wc_get_order( $order->get_id() )->get_status(), 'Orders in the selection should still be trashed' );
		$this->assertSame( 'publish', get_post_status( $post_id ), 'IDs that are not orders should be left untouched' );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Invoke the protected handle_bulk_actions() method, trapping its terminal redirect.
	 *
	 * handle_bulk_actions() ends in wp_safe_redirect()/exit; the wp_redirect filter below converts
	 * that redirect into a catchable exception so the assertions can run afterwards. Paths that
	 * return early (such as a blocked Empty Trash) never redirect and simply fall through.
	 */
	private function invoke_handle_bulk_actions(): void {
		$set_post_type = function () {
			$this->wp_post_type = get_post_type_object( $this->order_type );
		};
		$set_post_type->call( $this->sut );

		$throw_on_redirect = static function () {
			throw new \RuntimeException( esc_html( self::REDIRECT_SENTINEL ) );
		};
		add_filter( 'wp_redirect', $throw_on_redirect );

		try {
			$this->sut->handle_bulk_actions();
		} catch ( \RuntimeException $e ) {
			// Only the sentinel below is expected; anything else is a real failure.
			if ( self::REDIRECT_SENTINEL !== $e->getMessage() ) {
				throw $e;
			}
		} finally {
			remove_filter( 'wp_redirect', $throw_on_redirect );
		}
	}

	/**
	 * Create a user with a custom role and set it as the current user.
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
	 * Helper to read the order query args prepared by the list table.
	 *
	 * @return array
	 */
	private function get_order_query_args(): array {
		$getter = function () {
			return $this->order_query_args;
		};

		return $getter->call( $this->sut );
	}

	/**
	 * @testdox Submitting the search form with an empty search term keeps the cached-count fast path.
	 */
	public function test_empty_search_term_uses_cached_count_fast_path(): void {
		\WC_Helper_Order::create_order();

		$_REQUEST['s']             = '';
		$_REQUEST['search-filter'] = 'all';

		$this->sut->prepare_items();
		$query_args = $this->get_order_query_args();

		unset( $_REQUEST['s'], $_REQUEST['search-filter'] );

		$this->assertArrayNotHasKey( 's', $query_args, 'An empty search term should not be added to the query args' );
		$this->assertArrayNotHasKey( 'search_filter', $query_args, 'The search filter should not be added without a search term' );
		$this->assertTrue( $query_args['no_found_rows'] ?? false, 'An empty search should use cached order counts instead of a COUNT query' );
	}

	/**
	 * @testdox Searching with a term applies the selected search filter and uses a real results count.
	 */
	public function test_search_term_sets_search_filter_and_counts_results(): void {
		\WC_Helper_Order::create_order();

		$_REQUEST['s']             = 'some-term';
		$_REQUEST['search-filter'] = 'order_id';

		$this->sut->prepare_items();
		$query_args = $this->get_order_query_args();

		unset( $_REQUEST['s'], $_REQUEST['search-filter'] );

		$this->assertSame( 'some-term', $query_args['s'] ?? null, 'The search term should be added to the query args' );
		$this->assertSame( 'order_id', $query_args['search_filter'] ?? null, 'The selected search filter should be applied' );
		$this->assertArrayNotHasKey( 'no_found_rows', $query_args, 'Searches should count their actual results' );
	}

	/**
	 * @testdox When a filter modifies the query args via woocommerce_order_list_table_prepare_items_query_args, the cache fast path is not used.
	 */
	public function test_filter_modifying_query_args_disables_no_found_rows(): void {
		\WC_Helper_Order::create_order();

		$called = false;

		$callback = function ( $args ) use ( &$called ) {
			$called               = true;
			$args['meta_query'][] = array(
				'key'     => 'my_custom_meta_key',
				'value'   => 'any_value',
				'compare' => '=',
			);
			return $args;
		};
		add_filter( 'woocommerce_order_list_table_prepare_items_query_args', $callback );

		$this->sut->prepare_items();
		$query_args = $this->get_order_query_args();

		remove_filter( 'woocommerce_order_list_table_prepare_items_query_args', $callback );

		$this->assertTrue( $called, 'The filter should have been invoked' );
		$this->assertArrayNotHasKey( 'no_found_rows', $query_args, 'When a filter modifies the query args, the cache fast path should not be used' );
	}

	/**
	 * @testdox Without any filter modifying the query args, the cache fast path is still used.
	 */
	public function test_basic_query_still_uses_no_found_rows(): void {
		\WC_Helper_Order::create_order();

		$this->sut->prepare_items();
		$query_args = $this->get_order_query_args();

		$this->assertTrue( $query_args['no_found_rows'] ?? false, 'A basic query should use the cache fast path' );
	}
}
