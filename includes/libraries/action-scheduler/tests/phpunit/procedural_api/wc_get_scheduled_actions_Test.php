<?php

/**
 * Class wc_get_scheduled_actions_Test
 */
class wc_get_scheduled_actions_Test extends ActionScheduler_UnitTestCase {
	private $hooks = array();
	private $args = array();
	private $groups = array();

	public function setUp() {
		parent::setUp();

		$store = ActionScheduler::store();

		for ( $i = 0 ; $i < 10 ; $i++ ) {
			$this->hooks[$i] = md5(rand());
			$this->args[$i] = md5(rand());
			$this->groups[$i] = md5(rand());
		}

		for ( $i = 0 ; $i < 10 ; $i++ ) {
			for ( $j = 0 ; $j < 10 ; $j++  ) {
				$schedule = new ActionScheduler_SimpleSchedule( as_get_datetime_object( $j - 3 . 'days') );
				$group = $this->groups[ ( $i + $j ) % 10 ];
				$action = new ActionScheduler_Action( $this->hooks[$i], array($this->args[$j]), $schedule, $group );
				$store->save_action( $action );
			}
		}
	}

	public function test_date_queries() {
		$actions = wc_get_scheduled_actions(array(
			'date' => as_get_datetime_object(gmdate('Y-m-d 00:00:00')),
			'per_page' => -1,
		), 'ids');
		$this->assertCount(30, $actions);

		$actions = wc_get_scheduled_actions(array(
			'date' => as_get_datetime_object(gmdate('Y-m-d 00:00:00')),
			'date_compare' => '>=',
			'per_page' => -1,
		), 'ids');
		$this->assertCount(70, $actions);
	}

	public function test_hook_queries() {
		$actions = wc_get_scheduled_actions(array(
			'hook' => $this->hooks[2],
			'per_page' => -1,
		), 'ids');
		$this->assertCount(10, $actions);

		$actions = wc_get_scheduled_actions(array(
			'hook' => $this->hooks[2],
			'date' => as_get_datetime_object(gmdate('Y-m-d 00:00:00')),
			'per_page' => -1,
		), 'ids');
		$this->assertCount(3, $actions);
	}

	public function test_args_queries() {
		$actions = wc_get_scheduled_actions(array(
			'args' => array($this->args[5]),
			'per_page' => -1,
		), 'ids');
		$this->assertCount(10, $actions);

		$actions = wc_get_scheduled_actions(array(
			'args' => array($this->args[5]),
			'hook' => $this->hooks[3],
			'per_page' => -1,
		), 'ids');
		$this->assertCount(1, $actions);

		$actions = wc_get_scheduled_actions(array(
			'args' => array($this->args[5]),
			'hook' => $this->hooks[3],
			'date' => as_get_datetime_object(gmdate('Y-m-d 00:00:00')),
			'per_page' => -1,
		), 'ids');
		$this->assertCount(0, $actions);
	}

	public function test_group_queries() {
		$actions = wc_get_scheduled_actions(array(
			'group' => $this->groups[1],
			'per_page' => -1,
		), 'ids');
		$this->assertCount(10, $actions);

		$actions = wc_get_scheduled_actions(array(
			'group' => $this->groups[1],
			'hook' => $this->hooks[9],
			'per_page' => -1,
		), 'ids');
		$this->assertCount(1, $actions);
	}
}
 