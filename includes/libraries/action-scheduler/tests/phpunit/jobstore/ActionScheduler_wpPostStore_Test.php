<?php

/**
 * Class ActionScheduler_wpPostStore_Test
 * @group stores
 */
class ActionScheduler_wpPostStore_Test extends ActionScheduler_UnitTestCase {

	public function test_create_action() {
		$time = as_get_datetime_object();
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule);
		$store = new ActionScheduler_wpPostStore();
		$action_id = $store->save_action($action);

		$this->assertNotEmpty($action_id);
	}

	public function test_create_action_with_scheduled_date() {
		$time   = as_get_datetime_object( strtotime( '-1 week' ) );
		$action = new ActionScheduler_Action( 'my_hook', array(), new ActionScheduler_SimpleSchedule( $time ) );
		$store  = new ActionScheduler_wpPostStore();

		$action_id   = $store->save_action( $action, $time );
		$action_date = $store->get_date( $action_id );

		$this->assertEquals( $time->getTimestamp(), $action_date->getTimestamp() );
	}

	public function test_retrieve_action() {
		$time = as_get_datetime_object();
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule, 'my_group');
		$store = new ActionScheduler_wpPostStore();
		$action_id = $store->save_action($action);

		$retrieved = $store->fetch_action($action_id);
		$this->assertEquals($action->get_hook(), $retrieved->get_hook());
		$this->assertEqualSets($action->get_args(), $retrieved->get_args());
		$this->assertEquals($action->get_schedule()->next()->getTimestamp(), $retrieved->get_schedule()->next()->getTimestamp());
		$this->assertEquals($action->get_group(), $retrieved->get_group());
	}

	/**
	 * @expectedException ActionScheduler_InvalidActionException
	 * @dataProvider provide_bad_args
	 *
	 * @param string $content
	 */
	public function test_action_bad_args( $content ) {
		$store   = new ActionScheduler_wpPostStore();
		$post_id = wp_insert_post( array(
			'post_type'    => ActionScheduler_wpPostStore::POST_TYPE,
			'post_status'  => ActionScheduler_Store::STATUS_PENDING,
			'post_content' => $content,
		) );

		$store->fetch_action( $post_id );
	}

	public function provide_bad_args() {
		return array(
			array( '{"bad_json":true}}' ),
		);
	}

	public function test_cancel_action() {
		$time = as_get_datetime_object();
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule, 'my_group');
		$store = new ActionScheduler_wpPostStore();
		$action_id = $store->save_action($action);
		$store->cancel_action( $action_id );

		$fetched = $store->fetch_action( $action_id );
		$this->assertInstanceOf( 'ActionScheduler_CanceledAction', $fetched );
	}

	public function test_claim_actions() {
		$created_actions = array();
		$store = new ActionScheduler_wpPostStore();
		for ( $i = 3 ; $i > -3 ; $i-- ) {
			$time = as_get_datetime_object($i.' hours');
			$schedule = new ActionScheduler_SimpleSchedule($time);
			$action = new ActionScheduler_Action('my_hook', array($i), $schedule, 'my_group');
			$created_actions[] = $store->save_action($action);
		}

		$claim = $store->stake_claim();
		$this->assertInstanceof( 'ActionScheduler_ActionClaim', $claim );

		$this->assertCount( 3, $claim->get_actions() );
		$this->assertEqualSets( array_slice( $created_actions, 3, 3 ), $claim->get_actions() );
	}

	public function test_claim_actions_order() {
		$store           = new ActionScheduler_wpPostStore();
		$schedule        = new ActionScheduler_SimpleSchedule( as_get_datetime_object( '-1 hour' ) );
		$created_actions = array(
			$store->save_action( new ActionScheduler_Action( 'my_hook', array( 1 ), $schedule, 'my_group' ) ),
			$store->save_action( new ActionScheduler_Action( 'my_hook', array( 1 ), $schedule, 'my_group' ) ),
		);

		$claim = $store->stake_claim();
		$this->assertInstanceof( 'ActionScheduler_ActionClaim', $claim );

		// Verify uniqueness of action IDs.
		$this->assertEquals( 2, count( array_unique( $created_actions ) ) );

		// Verify the count and order of the actions.
		$claimed_actions = $claim->get_actions();
		$this->assertCount( 2, $claimed_actions );
		$this->assertEquals( $created_actions, $claimed_actions );

		// Verify the reversed order doesn't pass.
		$reversed_actions = array_reverse( $created_actions );
		$this->assertNotEquals( $reversed_actions, $claimed_actions );
	}

	public function test_duplicate_claim() {
		$created_actions = array();
		$store = new ActionScheduler_wpPostStore();
		for ( $i = 0 ; $i > -3 ; $i-- ) {
			$time = as_get_datetime_object($i.' hours');
			$schedule = new ActionScheduler_SimpleSchedule($time);
			$action = new ActionScheduler_Action('my_hook', array($i), $schedule, 'my_group');
			$created_actions[] = $store->save_action($action);
		}

		$claim1 = $store->stake_claim();
		$claim2 = $store->stake_claim();
		$this->assertCount( 3, $claim1->get_actions() );
		$this->assertCount( 0, $claim2->get_actions() );
	}

	public function test_release_claim() {
		$created_actions = array();
		$store = new ActionScheduler_wpPostStore();
		for ( $i = 0 ; $i > -3 ; $i-- ) {
			$time = as_get_datetime_object($i.' hours');
			$schedule = new ActionScheduler_SimpleSchedule($time);
			$action = new ActionScheduler_Action('my_hook', array($i), $schedule, 'my_group');
			$created_actions[] = $store->save_action($action);
		}

		$claim1 = $store->stake_claim();

		$store->release_claim( $claim1 );

		$claim2 = $store->stake_claim();
		$this->assertCount( 3, $claim2->get_actions() );
	}

	public function test_search() {
		$created_actions = array();
		$store = new ActionScheduler_wpPostStore();
		for ( $i = -3 ; $i <= 3 ; $i++ ) {
			$time = as_get_datetime_object($i.' hours');
			$schedule = new ActionScheduler_SimpleSchedule($time);
			$action = new ActionScheduler_Action('my_hook', array($i), $schedule, 'my_group');
			$created_actions[] = $store->save_action($action);
		}

		$next_no_args = $store->find_action( 'my_hook' );
		$this->assertEquals( $created_actions[0], $next_no_args );

		$next_with_args = $store->find_action( 'my_hook', array( 'args' => array( 1 ) ) );
		$this->assertEquals( $created_actions[4], $next_with_args );

		$non_existent = $store->find_action( 'my_hook', array( 'args' => array( 17 ) ) );
		$this->assertNull( $non_existent );
	}

	public function test_search_by_group() {
		$store = new ActionScheduler_wpPostStore();
		$schedule = new ActionScheduler_SimpleSchedule(as_get_datetime_object('tomorrow'));
		$abc = $store->save_action(new ActionScheduler_Action('my_hook', array(1), $schedule, 'abc'));
		$def = $store->save_action(new ActionScheduler_Action('my_hook', array(1), $schedule, 'def'));
		$ghi = $store->save_action(new ActionScheduler_Action('my_hook', array(1), $schedule, 'ghi'));

		$this->assertEquals( $abc, $store->find_action('my_hook', array('group' => 'abc')));
		$this->assertEquals( $def, $store->find_action('my_hook', array('group' => 'def')));
		$this->assertEquals( $ghi, $store->find_action('my_hook', array('group' => 'ghi')));
	}

	public function test_post_author() {
		$current_user = get_current_user_id();

		$time = as_get_datetime_object();
		$schedule = new ActionScheduler_SimpleSchedule($time);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule);
		$store = new ActionScheduler_wpPostStore();
		$action_id = $store->save_action($action);

		$post = get_post($action_id);
		$this->assertEquals(0, $post->post_author);

		$new_user = $this->factory->user->create_object(array(
			'user_login' => __FUNCTION__,
			'user_pass' => md5(rand()),
		));
		wp_set_current_user( $new_user );


		$schedule = new ActionScheduler_SimpleSchedule($time);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule);
		$action_id = $store->save_action($action);
		$post = get_post($action_id);
		$this->assertEquals(0, $post->post_author);

		wp_set_current_user($current_user);
	}

	/**
	 * @issue 13
	 */
	public function test_post_status_for_recurring_action() {
		$time = as_get_datetime_object('10 minutes');
		$schedule = new ActionScheduler_IntervalSchedule($time, HOUR_IN_SECONDS);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule);
		$store = new ActionScheduler_wpPostStore();
		$action_id = $store->save_action($action);

		$action = $store->fetch_action($action_id);
		$action->execute();
		$store->mark_complete( $action_id );

		$next = $action->get_schedule()->next( as_get_datetime_object() );
		$new_action_id = $store->save_action( $action, $next );

		$this->assertEquals('publish', get_post_status($action_id));
		$this->assertEquals('pending', get_post_status($new_action_id));
	}

	public function test_get_run_date() {
		$time = as_get_datetime_object('-10 minutes');
		$schedule = new ActionScheduler_IntervalSchedule($time, HOUR_IN_SECONDS);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule);
		$store = new ActionScheduler_wpPostStore();
		$action_id = $store->save_action($action);

		$this->assertEquals( $store->get_date($action_id)->getTimestamp(), $time->getTimestamp() );

		$action = $store->fetch_action($action_id);
		$action->execute();
		$now = as_get_datetime_object();
		$store->mark_complete( $action_id );

		$this->assertEquals( $store->get_date($action_id)->getTimestamp(), $now->getTimestamp() );

		$next = $action->get_schedule()->next( $now );
		$new_action_id = $store->save_action( $action, $next );

		$this->assertEquals( (int)($now->getTimestamp()) + HOUR_IN_SECONDS, $store->get_date($new_action_id)->getTimestamp() );
	}

	public function test_get_status() {
		$time = as_get_datetime_object('-10 minutes');
		$schedule = new ActionScheduler_IntervalSchedule($time, HOUR_IN_SECONDS);
		$action = new ActionScheduler_Action('my_hook', array(), $schedule);
		$store = new ActionScheduler_wpPostStore();
		$action_id = $store->save_action($action);

		$this->assertEquals( ActionScheduler_Store::STATUS_PENDING, $store->get_status( $action_id ) );

		$store->mark_complete( $action_id );
		$this->assertEquals( ActionScheduler_Store::STATUS_COMPLETE, $store->get_status( $action_id ) );

		$store->mark_failure( $action_id );
		$this->assertEquals( ActionScheduler_Store::STATUS_FAILED, $store->get_status( $action_id ) );
	}

	public function test_claim_actions_by_hooks() {
		$hook1    = __FUNCTION__ . '_hook_1';
		$hook2    = __FUNCTION__ . '_hook_2';
		$store    = new ActionScheduler_wpPostStore();
		$schedule = new ActionScheduler_SimpleSchedule( as_get_datetime_object( '-1 hour' ) );

		$action1 = $store->save_action( new ActionScheduler_Action( $hook1, array(), $schedule ) );
		$action2 = $store->save_action( new ActionScheduler_Action( $hook2, array(), $schedule ) );

		// Claiming no hooks should include all actions.
		$claim = $store->stake_claim( 10 );
		$this->assertEquals( 2, count( $claim->get_actions() ) );
		$this->assertTrue( in_array( $action1, $claim->get_actions() ) );
		$this->assertTrue( in_array( $action2, $claim->get_actions() ) );
		$store->release_claim( $claim );

		// Claiming a hook should claim only actions with that hook
		$claim = $store->stake_claim( 10, null, array( $hook1 ) );
		$this->assertEquals( 1, count( $claim->get_actions() ) );
		$this->assertTrue( in_array( $action1, $claim->get_actions() ) );
		$store->release_claim( $claim );

		// Claiming two hooks should claim actions with either of those hooks
		$claim = $store->stake_claim( 10, null, array( $hook1, $hook2 ) );
		$this->assertEquals( 2, count( $claim->get_actions() ) );
		$this->assertTrue( in_array( $action1, $claim->get_actions() ) );
		$this->assertTrue( in_array( $action2, $claim->get_actions() ) );
		$store->release_claim( $claim );

		// Claiming two hooks should claim actions with either of those hooks
		$claim = $store->stake_claim( 10, null, array( __METHOD__ . '_hook_3' ) );
		$this->assertEquals( 0, count( $claim->get_actions() ) );
		$this->assertFalse( in_array( $action1, $claim->get_actions() ) );
		$this->assertFalse( in_array( $action2, $claim->get_actions() ) );
		$store->release_claim( $claim );
	}

	/**
	 * @issue 121
	 */
	public function test_claim_actions_by_group() {
		$group1   = md5( rand() );
		$store    = new ActionScheduler_wpPostStore();
		$schedule = new ActionScheduler_SimpleSchedule( as_get_datetime_object( '-1 hour' ) );

		$action1 = $store->save_action( new ActionScheduler_Action( __METHOD__, array(), $schedule, $group1 ) );
		$action2 = $store->save_action( new ActionScheduler_Action( __METHOD__, array(), $schedule ) );

		// Claiming no group should include all actions.
		$claim = $store->stake_claim( 10 );
		$this->assertEquals( 2, count( $claim->get_actions() ) );
		$this->assertTrue( in_array( $action1, $claim->get_actions() ) );
		$this->assertTrue( in_array( $action2, $claim->get_actions() ) );
		$store->release_claim( $claim );

		// Claiming a group should claim only actions in that group.
		$claim = $store->stake_claim( 10, null, array(), $group1 );
		$this->assertEquals( 1, count( $claim->get_actions() ) );
		$this->assertTrue( in_array( $action1, $claim->get_actions() ) );
		$store->release_claim( $claim );
	}

	public function test_claim_actions_by_hook_and_group() {
		$hook1    = __FUNCTION__ . '_hook_1';
		$hook2    = __FUNCTION__ . '_hook_2';
		$hook3    = __FUNCTION__ . '_hook_3';
		$group1   = 'group_' . md5( rand() );
		$group2   = 'group_' . md5( rand() );
		$store    = new ActionScheduler_wpPostStore();
		$schedule = new ActionScheduler_SimpleSchedule( as_get_datetime_object( '-1 hour' ) );

		$action1 = $store->save_action( new ActionScheduler_Action( $hook1, array(), $schedule, $group1 ) );
		$action2 = $store->save_action( new ActionScheduler_Action( $hook2, array(), $schedule ) );
		$action3 = $store->save_action( new ActionScheduler_Action( $hook3, array(), $schedule, $group2 ) );

		// Claiming no hooks or group should include all actions.
		$claim = $store->stake_claim( 10 );
		$this->assertEquals( 3, count( $claim->get_actions() ) );
		$this->assertTrue( in_array( $action1, $claim->get_actions() ) );
		$this->assertTrue( in_array( $action2, $claim->get_actions() ) );
		$store->release_claim( $claim );

		// Claiming a group and hook should claim only actions in that group.
		$claim = $store->stake_claim( 10, null, array( $hook1 ), $group1 );
		$this->assertEquals( 1, count( $claim->get_actions() ) );
		$this->assertTrue( in_array( $action1, $claim->get_actions() ) );
		$store->release_claim( $claim );

		// Claiming a group and hook should claim only actions with that hook in that group.
		$claim = $store->stake_claim( 10, null, array( $hook2 ), $group1 );
		$this->assertEquals( 0, count( $claim->get_actions() ) );
		$this->assertFalse( in_array( $action1, $claim->get_actions() ) );
		$this->assertFalse( in_array( $action2, $claim->get_actions() ) );
		$store->release_claim( $claim );

		// Claiming a group and hook should claim only actions with that hook in that group.
		$claim = $store->stake_claim( 10, null, array( $hook1, $hook2 ), $group2 );
		$this->assertEquals( 0, count( $claim->get_actions() ) );
		$this->assertFalse( in_array( $action1, $claim->get_actions() ) );
		$this->assertFalse( in_array( $action2, $claim->get_actions() ) );
		$store->release_claim( $claim );
	}
}
