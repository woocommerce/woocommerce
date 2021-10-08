<?php
/**
 * Test the TaskList class.
 *
 * @package WooCommerce\Admin\Tests\OnboardingTasks
 */

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;

/**
 * class WC_Tests_OnboardingTasks_TaskList
 */
class WC_Tests_OnboardingTasks_TaskList extends WC_Unit_Test_Case {
	/**
	 * Task list.
	 *
	 * @var TaskList|null
	 */
	protected $list = null;

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->list = new TaskList(
			array(
				'id' => 'setup',
			)
		);
	}

	/**
	 * Tests that tracks events are correctly prefixed on the core list.
	 */
	public function test_setup_event_prefixing() {
		$list = new TaskList(
			array(
				'id' => 'setup',
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
				'id' => 'my_list',
			)
		);

		$this->assertEquals( 'my_list_tasklist_event', $list->prefix_event( 'event' ) );
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
	}

	/**
	 * Tests that lists can be shown after hiding.
	 */
	public function test_show() {
		$this->list->hide();
		$this->list->show();
		$this->assertFalse( $this->list->is_hidden() );
	}

	/**
	 * Tests adding a task.
	 */
	public function test_add_task() {
		$this->list->add_task( array( 'id' => 'my-task' ) );
		$this->assertEquals( 'my-task', $this->list->tasks[0]->id );
	}

	/**
	 * Tests getting viewable tasks.
	 */
	public function test_get_viewable_tasks() {
		$this->list->add_task(
			array(
				'id'       => 'viewable-task',
				'can_view' => true,
			)
		);
		$this->list->add_task(
			array(
				'id'       => 'not-viewable-task',
				'can_view' => false,
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
			array(
				'id'          => 'complete-task',
				'is_complete' => true,
			)
		);
		$this->list->add_task(
			array(
				'id'          => 'incomplete-task',
				'is_complete' => false,
			)
		);
		$this->assertFalse( $this->list->is_complete() );
	}

	/**
	 * Tests that a list is complete when all tasks are complete
	 */
	public function test_complete() {
		$this->list->add_task(
			array(
				'id'          => 'complete-task1',
				'is_complete' => true,
			)
		);
		$this->list->add_task(
			array(
				'id'          => 'complete-task-2',
				'is_complete' => true,
			)
		);
		$this->assertTrue( $this->list->is_complete() );
	}

	/**
	 * Tests that a list's completion status is saved.
	 */
	public function test_previous_completion() {
		$this->list->add_task(
			array(
				'id'          => 'complete-task1',
				'is_complete' => true,
			)
		);
		$this->list->add_task(
			array(
				'id'          => 'complete-task2',
				'is_complete' => true,
			)
		);
		$this->assertFalse( $this->list->has_previously_completed() );
		$this->list->get_json();
		$this->assertTrue( $this->list->has_previously_completed() );
	}

	/**
	 * Tests that a list and its tasks can be returned as JSON.
	 */
	public function test_get_json() {
		$this->list->add_task(
			array(
				'id'          => 'my-task',
				'is_complete' => true,
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

}
