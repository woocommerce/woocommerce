<?php
/**
 * Onboarding Tasks REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\OnboardingTasks;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

require_once __DIR__ . '/../features/onboarding-tasks/test-task.php';

// Wrokaround to suppress exif_read_data errors from
// https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/image.php#L835
define('WP_RUN_CORE_TESTS', false);

/**
 * WC Tests API Onboarding Tasks
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group run-in-separate-process
 */
class WC_Admin_Tests_API_Onboarding_Tasks extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/tasks';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Empty the db of any products.
		$query    = new \WC_Product_Query();
		$products = $query->get_products();
		foreach ( $products as $product ) {
			$product->delete( true );
		}

		// Resetting task list options and lists.
		update_option( Task::DISMISSED_OPTION, array() );
		TaskLists::clear_lists();

	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->remove_color_or_logo_attribute_taxonomy();
		TaskLists::clear_lists();
		TaskLists::init_default_lists();
	}

	/**
	 * Remove product attributes that where created in previous tests.
	 */
	public function remove_color_or_logo_attribute_taxonomy() {
		$taxonomies = get_taxonomies();
		foreach ( (array) $taxonomies as $taxonomy ) {
			// pa - product attribute.
			if ( 'pa_color' === $taxonomy || 'pa_logo' === $taxonomy ) {
				unregister_taxonomy( $taxonomy );
			}
		}
	}

	/**
	 * Test that sample product data is imported.
	 */
	public function test_import_sample_products() {
		wp_set_current_user( $this->user );

		$this->remove_color_or_logo_attribute_taxonomy();

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/import_sample_products' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'failed', $data );
		$this->assertEquals( 0, count( $data['failed'] ) );
		$this->assertArrayHasKey( 'imported', $data );
		$this->assertArrayHasKey( 'skipped', $data );
		// There might be previous products present.
		if ( 0 === count( $data['skipped'] ) ) {
			$this->assertGreaterThan( 1, count( $data['imported'] ) );
		}
		$this->assertArrayHasKey( 'updated', $data );
		$this->assertEquals( 0, count( $data['updated'] ) );
	}

	/**
	 * Test creating a product from a template name.
	 */
	public function test_create_product_from_template() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/create_product_from_template' );
		$request->set_param( 'template_name', 'physical' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'id', $data );
		$product = wc_get_product( $data['id'] );
		$this->assertEquals( 'auto-draft', $product->get_status() );
		$this->assertEquals( 'simple', $product->get_type() );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/create_product_from_template' );
		$request->set_param( 'template_name', 'digital' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'id', $data );
		$product = wc_get_product( $data['id'] );
		$this->assertEquals( 'auto-draft', $product->get_status() );
		$this->assertEquals( 'simple', $product->get_type() );
	}

	/**
	 * Test that we get an error when template_name does not exist.
	 */
	public function test_create_product_from_wrong_template_name() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/create_product_from_template' );
		$request->set_param( 'template_name', 'random' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * Test that Tasks data is returned by the endpoint.
	 */
	public function test_create_homepage() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/create_homepage' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $data['status'] );
		$this->assertEquals( get_option( 'woocommerce_onboarding_homepage_post_id' ), $data['post_id'] );
		$this->assertEquals( htmlspecialchars_decode( get_edit_post_link( get_option( 'woocommerce_onboarding_homepage_post_id' ) ) ), $data['edit_post_link'] );
	}

	/**
	 * Test that the default homepage template can be filtered.
	 */
	public function test_homepage_template_can_be_filtered() {
		wp_set_current_user( $this->user );

		add_filter(
			'woocommerce_admin_onboarding_homepage_template',
			function ( $template ) {
				return 'Custom post content';
			}
		);

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/create_homepage' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 'Custom post content', get_the_content( null, null, $data['post_id'] ) );
	}

	/**
	 * Test that a task can be dismissed.
	 * @group tasklist
	 */
	public function test_task_can_be_dismissed() {
		wp_set_current_user( $this->user );

		TaskLists::add_list(
			array(
				'id' => 'test-list',
			)
		);

		TaskLists::add_task(
			'test-list',
			new TestTask(
				TaskLists::get_list( 'test-list' ),
				array(
					'id'             => 'test-task',
					'title'          => 'Test Task',
					'is_dismissable' => true,
				)
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/test-task/dismiss' );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$task = TaskLists::get_task( 'test-task' );

		$this->assertEquals( $data['isDismissed'], true );
		$this->assertEquals( $task->is_dismissed(), true );
	}

	/**
	 * Test that a dismissed task can be undone.
	 * @group tasklist
	 */
	public function test_dismissed_task_can_be_undone() {
		wp_set_current_user( $this->user );

		TaskLists::add_list(
			array(
				'id' => 'test-list',
			)
		);

		TaskLists::add_task(
			'test-list',
			new TestTask(
				TaskLists::get_list( 'test-list' ),
				array(
					'id'             => 'test-task',
					'title'          => 'Test Task',
					'is_dismissable' => true,
				)
			)
		);

		$task = TaskLists::get_task( 'test-task' );

		$task->dismiss();

		$this->assertEquals( $task->is_dismissed(), true );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/test-task/undo_dismiss' );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$task_after_request = TaskLists::get_task( 'test-task' );

		$this->assertEquals( $task_after_request->is_dismissed(), false );
	}

	/**
	 * Test that dismiss endpoint returns error for invalid task.
	 * @group tasklist
	 */
	public function test_dismissed_task_invalid() {
		$this->markTestSkipped( 'Skipped temporarily due to change in endpoint behavior.' );
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/test-task/dismiss' );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertEquals( $response_data['data']['status'], 404 );
		$this->assertEquals( $response_data['code'], 'woocommerce_rest_invalid_task' );
	}

	/**
	 * Test that a task list can be hidden.
	 * @group tasklist
	 */
	public function test_task_list_can_be_hidden() {
		wp_set_current_user( $this->user );

		TaskLists::add_list(
			array(
				'id' => 'test-list',
			)
		);

		TaskLists::add_task(
			'test-list',
			new TestTask(
				TaskLists::get_list( 'test-list' ),
				array(
					'id'             => 'test-task',
					'title'          => 'Test Task',
					'is_dismissable' => true,
				)
			)
		);

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/test-list/hide' );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$list = TaskLists::get_list( 'test-list' );

		$this->assertEquals( $list->is_hidden(), true );
		$this->assertEquals( $response_data['isHidden'], true );
	}

	/**
	 * Test that hide endpoint returns error for invalid task.
	 * @group tasklist
	 */
	public function test_task_list_hidden_invalid_list() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/test-list/hide' );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertEquals( $response_data['data']['status'], 404 );
		$this->assertEquals( $response_data['code'], 'woocommerce_rest_invalid_task_list' );
	}


	/**
	 * Test that task lists can be fetched.
	 * @group tasklist
	 */
	public function test_task_list_can_be_fetched() {
		wp_set_current_user( $this->user );

		TaskLists::add_list(
			array(
				'id' => 'test-list',
			)
		);

		TaskLists::add_task(
			'test-list',
			new TestTask(
				TaskLists::get_list( 'test-list' ),
				array(
					'id'             => 'test-task',
					'title'          => 'Test Task',
					'is_dismissable' => true,
				)
			)
		);

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$test_list = null;

		foreach ( $response_data as $task_list ) {
			if ( 'test-list' === $task_list['id'] ) {
				$test_list = $task_list;
			}
		}

		$test_task = $test_list['tasks'][0];

		$this->assertEquals( $test_task['id'], 'test-task' );
		$this->assertEquals( $test_task['isDismissable'], true );
	}

}
