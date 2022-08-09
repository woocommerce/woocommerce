<?php
/**
 * Test the TaskList class.
 *
 * @package WooCommerce\Admin\Tests\OnboardingTasks
 */

/**
 * Test task fixture.
 */
require_once __DIR__ . '/test-task.php';

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * class WC_Admin_Tests_OnboardingTasks_TaskList
 */
class WC_Admin_Tests_OnboardingTasks_TaskList extends WC_Unit_Test_Case {

	/**
	 * Task list.
	 *
	 * @var TaskList|null
	 */
	protected $list = null;

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_allow_tracking', 'yes' );

		$this->list = new TaskList(
			array(
				'id' => 'setup',
			)
		);
	}

	/**
	 * Tear down test data. Called after every test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-footer-pixel.php';
		WC_Tracks_Footer_Pixel::clear_events();
	}

	/**
	 * Tests that tracks events are correctly prefixed on the core list.
	 */
	public function test_setup_event_prefixing() {
		$list = new TaskList(
			array(
				'id'           => 'setup',
				'event_prefix' => 'tasklist_',
			)
		);

		$this->assertEquals( 'tasklist_event', $list->prefix_event( 'event' ) );
	}

	/**
	 * Tests that tracks events are correctly prefixed on the other lists.
	 */
	public function test_event_prefixing() {
		$list = new TaskList(
			array(
				'id' => 'extended',
			)
		);

		$this->assertEquals( 'extended_tasklist_event', $list->prefix_event( 'event' ) );
	}

	/**
	 * Tests that lists are not hidden by default.
	 */
	public function test_visibility() {
		$this->assertFalse( $this->list->is_hidden() );
	}

	/**
	 * Tests that lists can be hidden.
	 */
	public function test_hide() {
		$this->list->hide();
		$this->assertTrue( $this->list->is_hidden() );
		$this->assertRecordedTracksEvent( 'wcadmin_setup_tasklist_completed' );
	}

	/**
	 * Tests that lists can be shown after hiding.
	 */
	public function test_unhide() {
		$this->list->hide();
		$this->list->unhide();
		$this->assertFalse( $this->list->is_hidden() );
	}

	/**
	 * Tests that lists can be hidden.
	 */
	public function test_visible() {
		$this->assertFalse( $this->list->is_visible() );
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array( 'id' => 'my-task' )
			)
		);
		$this->assertTrue( $this->list->is_visible() );
	}

	/**
	 * Tests that lists can be hidden.
	 */
	public function test_visible_prop() {
		$this->list->visible = false;
		$this->assertFalse( $this->list->is_visible() );
	}

	/**
	 * Tests adding a task.
	 */
	public function test_add_task() {
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array( 'id' => 'my-task' )
			)
		);
		$this->assertEquals( 'my-task', $this->list->tasks[0]->id );
	}

	/**
	 * Tests getting viewable tasks.
	 */
	public function test_get_viewable_tasks() {
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'       => 'viewable-task',
					'can_view' => true,
				)
			)
		);
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'       => 'not-viewable-task',
					'can_view' => false,
				)
			)
		);
		$viewable_tasks = $this->list->get_viewable_tasks();
		$this->assertCount( 1, $viewable_tasks );
		$this->assertEquals( 'viewable-task', $viewable_tasks[0]->id );
	}


	/**
	 * Tests that a list is not complete when a task is not complete.
	 */
	public function test_incomplete() {
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'complete-task',
					'is_complete' => true,
				)
			)
		);
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'incomplete-task',
					'is_complete' => false,
				)
			)
		);
		$this->assertFalse( $this->list->is_complete() );
	}

	/**
	 * Tests that a list is complete when all tasks are complete
	 */
	public function test_complete() {
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'complete-task1',
					'is_complete' => true,
				)
			)
		);
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'complete-task-2',
					'is_complete' => true,
				)
			)
		);
		$this->assertTrue( $this->list->is_complete() );
		$this->list->get_json();
		$this->assertRecordedTracksEvent( 'wcadmin_setup_tasklist_tasks_completed' );
	}

	/**
	 * Tests that a list's completion status is saved.
	 */
	public function test_previous_completion() {
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'complete-task1',
					'is_complete' => true,
				)
			)
		);
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'complete-task2',
					'is_complete' => true,
				)
			)
		);
		$this->assertNotRecordedTracksEvent( 'wcadmin_setup_tasklist_tasks_completed' );
		$this->assertFalse( $this->list->has_previously_completed() );
		$this->list->get_json();
		$this->assertRecordedTracksEvent( 'wcadmin_setup_tasklist_tasks_completed' );
		$this->assertTrue( $this->list->has_previously_completed() );
	}

	/**
	 * Tests that a list and its tasks can be returned as JSON.
	 */
	public function test_get_json() {
		$this->list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'my-task',
					'is_complete' => true,
				)
			)
		);
		$json = $this->list->get_json();
		$this->assertContains( 'id', $json );
		$this->assertContains( 'title', $json );
		$this->assertContains( 'isHidden', $json );
		$this->assertContains( 'isVisible', $json );
		$this->assertContains( 'isComplete', $json );
		$this->assertContains( 'tasks', $json );
		$this->assertContains( 'isComplete', $json['tasks'][0] );
	}

	/**
	 * Adds a couple tasks to the provided list.
	 *
	 * @param TaskList $list list to add tasks to.
	 */
	public function add_test_tasks( $list ) {
		$list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'task-1',
					'can_view'    => true,
					'level'       => 1,
					'is_complete' => true,
				)
			)
		);
		$list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'task-2',
					'can_view'    => true,
					'is_complete' => false,
				)
			)
		);
		$list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'task-3',
					'can_view'    => true,
					'level'       => 2,
					'is_complete' => false,
				)
			)
		);
		$list->add_task(
			new TestTask(
				new TaskList(),
				array(
					'id'          => 'task-4',
					'can_view'    => true,
					'level'       => 1,
					'is_complete' => false,
				)
			)
		);
	}

	/**
	 * Test task list sort_tasks without sort_by config.
	 */
	public function test_sort_tasks_without_sort_by() {
		$this->add_test_tasks( $this->list );
		$this->list->sort_tasks();
		$json = $this->list->get_json();
		$this->assertEquals( array_column( $json['tasks'], 'id' ), array( 'task-1', 'task-2', 'task-3', 'task-4' ) );
	}

	/**
	 * Test task list sort_tasks with sort_by config for is_complete.
	 */
	public function test_sort_tasks_with_sort_by() {
		$list = new TaskList(
			array(
				'id'      => 'setup',
				'sort_by' => array(
					array(
						'key'   => 'is_complete',
						'order' => 'asc',
					),
				),
			)
		);
		$this->add_test_tasks( $list );
		$list->sort_tasks();
		$json = $list->get_json();
		$this->assertEquals( $json['tasks'][3]['id'], 'task-1' );
	}

	/**
	 * Test task list sort_tasks with sort_by config for is_complete and level.
	 */
	public function test_sort_tasks_with_sort_by_multiple_items() {
		$list = new TaskList(
			array(
				'id'      => 'setup',
				'sort_by' => array(
					array(
						'key'   => 'is_complete',
						'order' => 'asc',
					),
					array(
						'key'   => 'level',
						'order' => 'asc',
					),
				),
			)
		);
		$this->add_test_tasks( $list );
		$list->sort_tasks();
		$json = $list->get_json();
		$this->assertEquals( array_column( $json['tasks'], 'id' ), array( 'task-4', 'task-3', 'task-2', 'task-1' ) );
	}

	/**
	 * Test task list sort_tasks with custom config.
	 */
	public function test_sort_tasks_with_passed_in_sort_by_config() {
		$list = new TaskList(
			array(
				'id'      => 'setup',
				'sort_by' => array(
					array(
						'key'   => 'is_complete',
						'order' => 'asc',
					),
					array(
						'key'   => 'level',
						'order' => 'asc',
					),
				),
			)
		);
		$this->add_test_tasks( $list );
		$list->sort_tasks(
			array(
				array(
					'key'   => 'level',
					'order' => 'desc',
				),
			)
		);
		$json = $list->get_json();
		$this->assertEquals( array_column( $json['tasks'], 'id' ), array( 'task-2', 'task-3', 'task-1', 'task-4' ) );
	}

	/**
	 * Test that the list ID is retreived.
	 */
	public function test_get_list_id() {
		$this->assertEquals( 'setup', $this->list->get_list_id() );
	}

	/**
	 * Test that tracks events are recorded with the correct IDs.
	 */
	public function test_record_tracks_event() {
		$this->assertEquals( 'setup_tasklist_test_event', $this->list->record_tracks_event( 'test_event' ) );
	}

	/**
	 * Test explicit behavior of defaulting to two_column layout after setup tasklist is hidden.
	 */
	public function test_default_layout_after_hide_setup_tasklist() {
		$list = new TaskList(
			array(
				'id' => 'setup',
			)
		);
		$list->hide();
		$this->assertEquals( get_option( 'woocommerce_default_homepage_layout', null ), 'two_columns' );
		delete_option( 'woocommerce_default_homepage_layout' );
	}

	/**
	 * Test explicit behavior of defaulting to two_column layout after setup tasklist is completed.
	 */
	public function test_default_layout_after_complete_setup_tasklist() {
		$list = new TaskList(
			array(
				'id' => 'setup',
			)
		);
		$list->possibly_track_completion();
		$this->assertEquals( get_option( 'woocommerce_default_homepage_layout', null ), 'two_columns' );
		delete_option( 'woocommerce_default_homepage_layout' );
	}
}
