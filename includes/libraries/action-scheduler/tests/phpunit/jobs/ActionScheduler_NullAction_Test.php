<?php

/**
 * Class ActionScheduler_NullAction_Test
 * @group actions
 */
class ActionScheduler_NullAction_Test extends ActionScheduler_UnitTestCase {
	public function test_null_action() {
		$action = new ActionScheduler_NullAction();

		$this->assertEmpty($action->get_hook());
		$this->assertEmpty($action->get_args());
		$this->assertNull($action->get_schedule()->next());
	}
}
 