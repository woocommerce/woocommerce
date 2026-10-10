<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Onboarding;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Internal\Admin\Onboarding\MarketplaceTaskExperiment;
use Automattic\WooCommerce\Internal\Admin\WCAdminUser;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the MarketplaceTaskExperiment class.
 */
class MarketplaceTaskExperimentTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MarketplaceTaskExperiment
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		TaskLists::clear_lists();
		TaskLists::init_default_lists();

		$this->sut = new MarketplaceTaskExperiment();
		wc_get_container()->replace( MarketplaceTaskExperiment::class, $this->sut );

		// WP_HTTP_TestCase records every request in $this->http_requests; never reach the live ExPlat API.
		$this->http_responder = fn() => array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'variations' => array( MarketplaceTaskExperiment::EXPERIMENT_NAME => MarketplaceTaskExperiment::FIRST_POSITION ),
					'ttl'        => 60,
				)
			),
		);

		update_option( 'woocommerce_allow_tracking', 'yes' );
		$_COOKIE['tk_ai'] = 'test-anon-id';

		// Pin the clock before the end date so these tests keep passing after the experiment ends.
		$this->register_legacy_proxy_function_mocks( array( 'time' => fn() => 1803859200 - 1 ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			unset( $_COOKIE['tk_ai'] );
			$this->reset_legacy_proxy_mocks();
			wc_get_container()->reset_all_replacements();
			TaskLists::clear_lists();
			TaskLists::init_default_lists();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Name of the backoff transient set after a failed request (mirrors the private constant in MarketplaceTaskExperiment).
	 */
	private const BACKOFF_TRANSIENT = 'woocommerce_marketplace_task_experiment_backoff';

	/**
	 * Get the Marketplace task from the default "extended" list.
	 *
	 * @return Task
	 */
	private function get_task(): Task {
		return TaskLists::get_task( 'extend-store', 'extended' );
	}

	/**
	 * @testdox Should return control without contacting ExPlat when tracking is disabled.
	 */
	public function test_returns_control_without_request_when_tracking_disabled(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		set_transient( 'abtest_variation_' . MarketplaceTaskExperiment::EXPERIMENT_NAME, MarketplaceTaskExperiment::COPY_FREE_AND_PAID );

		$this->assertSame( MarketplaceTaskExperiment::CONTROL, $this->sut->get_variation( $this->get_task() ) );
		$this->assertSame( 'Enhance your store with extensions', $this->get_task()->get_title() );
		$this->assertCount( 0, $this->http_requests, 'No assignment should be requested without tracking consent' );
	}

	/**
	 * @testdox Should use the title for the assigned variation.
	 *
	 * @testWith ["copy_payments_shipping_marketing", "Add payments, shipping and marketing extensions"]
	 *           ["copy_free_and_paid", "Browse free and paid extensions"]
	 *           ["first_position", "Enhance your store with extensions"]
	 *           ["unknown_variation", "Enhance your store with extensions"]
	 *
	 * @param string $variation      Cached ExPlat assignment.
	 * @param string $expected_title Expected task title.
	 */
	public function test_title_matches_assigned_variation( string $variation, string $expected_title ): void {
		set_transient( 'abtest_variation_' . MarketplaceTaskExperiment::EXPERIMENT_NAME, $variation );

		$this->assertSame( $expected_title, $this->get_task()->get_title() );
		$this->assertCount( 0, $this->http_requests, 'A cached assignment should not trigger a request' );
	}

	/**
	 * @testdox Should move the task to the top of the extended list only for first_position.
	 *
	 * @testWith ["first_position", true]
	 *           ["copy_free_and_paid", false]
	 *
	 * @param string $variation    Cached ExPlat assignment.
	 * @param bool   $expect_moved Whether the task should be first.
	 */
	public function test_tasks_endpoint_moves_task_first_for_first_position( string $variation, bool $expect_moved ): void {
		$default_ids = $this->prepare_extended_list( $variation );

		$request = new WP_REST_Request( 'GET', '/wc-admin/onboarding/tasks' );
		$request->set_param( 'ids', array( 'extended' ) );
		$ids = array_column( rest_get_server()->dispatch( $request )->get_data()[0]['tasks'], 'id' );

		$this->assertSame( $expect_moved ? $this->move_task_first( $default_ids ) : $default_ids, $ids );
	}

	/**
	 * @testdox Should return the task first when the extended list is unhidden for first_position.
	 */
	public function test_unhide_endpoint_moves_task_first_for_first_position(): void {
		$default_ids = $this->prepare_extended_list( MarketplaceTaskExperiment::FIRST_POSITION );
		update_option( TaskList::HIDDEN_OPTION, array( 'extended' ) );
		$this->assertSame( MarketplaceTaskExperiment::CONTROL, $this->sut->get_variation( $this->get_task() ), 'A hidden list should resolve to control' );

		$data = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/wc-admin/onboarding/tasks/extended/unhide' ) )->get_data();

		$this->assertSame( $this->move_task_first( $default_ids ), array_column( $data['tasks'], 'id' ) );
	}

	/**
	 * Seed the assignment, log in as an admin, and return the extended list's default task order.
	 *
	 * @param string $variation Cached ExPlat assignment.
	 * @return string[]
	 */
	private function prepare_extended_list( string $variation ): array {
		set_transient( 'abtest_variation_' . MarketplaceTaskExperiment::EXPERIMENT_NAME, $variation );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$default_ids = array_map( fn( $task ) => $task->get_id(), TaskLists::get_list( 'extended' )->get_viewable_tasks() );
		$this->assertNotSame( 'extend-store', $default_ids[0], 'The task should not be first by default' );

		return $default_ids;
	}

	/**
	 * Expected order with the Marketplace task first and the rest unchanged.
	 *
	 * @param string[] $ids Default task order.
	 * @return string[]
	 */
	private function move_task_first( array $ids ): array {
		return array_merge( array( 'extend-store' ), array_values( array_diff( $ids, array( 'extend-store' ) ) ) );
	}

	/**
	 * @testdox Should fetch the assignment from ExPlat and cache it when no transient is set.
	 *
	 * @testWith ["copy_free_and_paid", "copy_free_and_paid"]
	 *           [null, "control"]
	 *
	 * @param string|null $assigned Variation in the ExPlat response, or null for an empty response.
	 * @param string      $expected Expected variation.
	 */
	public function test_fetches_assignment_from_explat( ?string $assigned, string $expected ): void {
		$body                 = null === $assigned
			? array(
				'variations'  => new \stdClass(),
				'assignments' => new \stdClass(),
				'ttl'         => 7200,
			)
			: array(
				'variations' => array( MarketplaceTaskExperiment::EXPERIMENT_NAME => $assigned ),
				'ttl'        => 3600,
			);
		$this->http_responder = fn() => array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( $body ),
		);

		$this->assertSame( $expected, $this->sut->get_variation( $this->get_task() ) );
		$this->assertCount( 1, $this->http_requests );
		wp_parse_str( (string) wp_parse_url( $this->http_requests[0]['url'], PHP_URL_QUERY ), $args );
		$this->assertSame( MarketplaceTaskExperiment::EXPERIMENT_NAME, $args['experiment_name'] );
		$this->assertSame( 'test-anon-id', $args['anon_id'] );
		$this->assertSame( $expected, get_transient( 'abtest_variation_' . MarketplaceTaskExperiment::EXPERIMENT_NAME ) );
		$this->assertFalse( get_transient( self::BACKOFF_TRANSIENT ) );
	}

	/**
	 * @testdox Should fall back to control and stop requesting for a while when the request fails.
	 *
	 * @testWith ["wp_error"]
	 *           ["server_error"]
	 *
	 * @param string $failure Kind of failed response.
	 */
	public function test_backs_off_when_request_fails( string $failure ): void {
		$this->http_responder = 'wp_error' === $failure
			? fn() => new \WP_Error( 'http_request_failed', 'Offline' )
			: fn() => array(
				'response' => array( 'code' => 500 ),
				'body'     => 'Internal Server Error',
			);

		$this->assertSame( MarketplaceTaskExperiment::CONTROL, $this->sut->get_variation( $this->get_task() ) );
		$this->assertNotEmpty( get_transient( self::BACKOFF_TRANSIENT ), 'A failed request should set the backoff' );

		$this->assertSame( MarketplaceTaskExperiment::CONTROL, ( new MarketplaceTaskExperiment() )->get_variation( $this->get_task() ) );
		$this->assertCount( 1, $this->http_requests, 'No request should be made during the backoff' );
	}

	/**
	 * @testdox Should not request an assignment when the task is dismissed, complete, its list is hidden, there is no tk_ai cookie, tracking is filtered off, or the experiment has ended.
	 *
	 * @testWith ["dismissed"]
	 *           ["visited"]
	 *           ["hidden_list"]
	 *           ["no_anon_id"]
	 *           ["tracking_filtered_off"]
	 *           ["ended"]
	 *
	 * @param string $state Task state that should skip the request.
	 */
	public function test_skips_request_for_inactive_task( string $state ): void {
		if ( 'dismissed' === $state ) {
			update_option( Task::DISMISSED_OPTION, array( 'extend-store' ) );
		} elseif ( 'visited' === $state ) {
			$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
			wp_set_current_user( $user_id );
			WCAdminUser::update_user_data_field( $user_id, 'task_list_tracked_started_tasks', wp_json_encode( array( 'extend-store' => 1 ) ) );
		} elseif ( 'hidden_list' === $state ) {
			update_option( TaskList::HIDDEN_OPTION, array( 'extended' ) );
		} elseif ( 'no_anon_id' === $state ) {
			unset( $_COOKIE['tk_ai'] );
		} elseif ( 'tracking_filtered_off' === $state ) {
			add_filter( 'woocommerce_apply_user_tracking', '__return_false' );
		} else {
			$this->register_legacy_proxy_function_mocks( array( 'time' => fn() => 1803859200 ) );
		}

		$this->assertSame( MarketplaceTaskExperiment::CONTROL, $this->sut->get_variation( $this->get_task() ) );
		$this->assertCount( 0, $this->http_requests, 'No assignment should be requested' );
		$this->assertFalse( get_transient( self::BACKOFF_TRANSIENT ), 'A skipped request should not set the backoff' );
	}
}
