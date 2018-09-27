<?php

/**
 * Class ActionScheduler_wpCommentLogger_Test
 * @package test_cases\logging
 */
class ActionScheduler_wpCommentLogger_Test extends ActionScheduler_UnitTestCase {
	public function test_default_logger() {
		$logger = ActionScheduler::logger();
		$this->assertInstanceOf( 'ActionScheduler_Logger', $logger );
		$this->assertInstanceOf( 'ActionScheduler_wpCommentLogger', $logger );
	}

	public function test_add_log_entry() {
		$action_id = as_schedule_single_action( time(), 'a hook' );
		$logger = ActionScheduler::logger();
		$message = 'Logging that something happened';
		$log_id = $logger->log( $action_id, $message );
		$entry = $logger->get_entry( $log_id );

		$this->assertEquals( $action_id, $entry->get_action_id() );
		$this->assertEquals( $message, $entry->get_message() );
	}

	public function test_add_log_datetime() {
		$action_id = as_schedule_single_action( time(), 'a hook' );
		$logger    = ActionScheduler::logger();
		$message   = 'Logging that something happened';
		$date      = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$log_id    = $logger->log( $action_id, $message, $date );
		$entry     = $logger->get_entry( $log_id );

		$this->assertEquals( $action_id, $entry->get_action_id() );
		$this->assertEquals( $message, $entry->get_message() );

		$date      = new ActionScheduler_DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$log_id    = $logger->log( $action_id, $message, $date );
		$entry     = $logger->get_entry( $log_id );

		$this->assertEquals( $action_id, $entry->get_action_id() );
		$this->assertEquals( $message, $entry->get_message() );
	}

	public function test_null_log_entry() {
		$logger = ActionScheduler::logger();
		$entry = $logger->get_entry( 1 );
		$this->assertEquals( '', $entry->get_action_id() );
		$this->assertEquals( '', $entry->get_message() );
	}

	public function test_erroneous_entry_id() {
		$comment = wp_insert_comment(array(
			'comment_post_ID' => 1,
			'comment_author' => 'test',
			'comment_content' => 'this is not a log entry',
		));
		$logger = ActionScheduler::logger();
		$entry = $logger->get_entry( $comment );
		$this->assertEquals( '', $entry->get_action_id() );
		$this->assertEquals( '', $entry->get_message() );
	}

	public function test_storage_comments() {
		$action_id = as_schedule_single_action( time(), 'a hook' );
		$logger = ActionScheduler::logger();
		$logs = $logger->get_logs( $action_id );
		$expected = new ActionScheduler_LogEntry( $action_id, 'action created' );
		$this->assertTrue( in_array( $this->log_entry_to_array( $expected ) , $this->log_entry_to_array( $logs ) ) );
	}

	protected function log_entry_to_array( $logs ) {
		if ( $logs instanceof ActionScheduler_LogEntry ) {
			return array( 'action_id' => $logs->get_action_id(), 'message' => $logs->get_message() );
		}

		foreach ( $logs as $id => $log) {
			$logs[ $id ] = array( 'action_id' => $log->get_action_id(), 'message' => $log->get_message() );
		}

		return $logs;
	}

	public function test_execution_comments() {
		$action_id = as_schedule_single_action( time(), 'a hook' );
		$logger = ActionScheduler::logger();
		$started = new ActionScheduler_LogEntry( $action_id, 'action started' );
		$finished = new ActionScheduler_LogEntry( $action_id, 'action complete' );

		$runner = new ActionScheduler_QueueRunner();
		$runner->run();

		$logs = $logger->get_logs( $action_id );
		$this->assertTrue( in_array( $this->log_entry_to_array( $started ), $this->log_entry_to_array( $logs ) ) );
		$this->assertTrue( in_array( $this->log_entry_to_array( $finished ), $this->log_entry_to_array( $logs ) ) );
	}

	public function test_failed_execution_comments() {
		$hook = md5(rand());
		add_action( $hook, array( $this, '_a_hook_callback_that_throws_an_exception' ) );
		$action_id = as_schedule_single_action( time(), $hook );
		$logger = ActionScheduler::logger();
		$started = new ActionScheduler_LogEntry( $action_id, 'action started' );
		$finished = new ActionScheduler_LogEntry( $action_id, 'action complete' );
		$failed = new ActionScheduler_LogEntry( $action_id, 'action failed: Execution failed' );

		$runner = new ActionScheduler_QueueRunner();
		$runner->run();

		$logs = $logger->get_logs( $action_id );
		$this->assertTrue( in_array( $this->log_entry_to_array( $started ), $this->log_entry_to_array( $logs ) ) );
		$this->assertFalse( in_array( $this->log_entry_to_array( $finished ), $this->log_entry_to_array( $logs ) ) );
		$this->assertTrue( in_array( $this->log_entry_to_array( $failed ), $this->log_entry_to_array( $logs ) ) );
	}

	public function test_fatal_error_comments() {
		$hook = md5(rand());
		$action_id = as_schedule_single_action( time(), $hook );
		$logger = ActionScheduler::logger();
		do_action( 'action_scheduler_unexpected_shutdown', $action_id, array(
			'type' => E_ERROR,
			'message' => 'Test error',
			'file' => __FILE__,
			'line' => __LINE__,
		));

		$logs = $logger->get_logs( $action_id );
		$found_log = FALSE;
		foreach ( $logs as $l ) {
			if ( strpos( $l->get_message(), 'unexpected shutdown' ) === 0 ) {
				$found_log = TRUE;
			}
		}
		$this->assertTrue( $found_log, 'Unexpected shutdown log not found' );
	}

	public function test_canceled_action_comments() {
		$action_id = as_schedule_single_action( time(), 'a hook' );
		as_unschedule_action( 'a hook' );
		$logger = ActionScheduler::logger();
		$logs = $logger->get_logs( $action_id );
		$expected = new ActionScheduler_LogEntry( $action_id, 'action canceled' );
		$this->assertTrue( in_array( $this->log_entry_to_array( $expected ), $this->log_entry_to_array( $logs ) ) );
	}

	public function _a_hook_callback_that_throws_an_exception() {
		throw new RuntimeException('Execution failed');
	}

	public function test_filtering_of_get_comments() {
		$post_id = $this->factory->post->create_object(array(
			'post_title' => __FUNCTION__,
		));
		$comment_id = $this->factory->comment->create_object(array(
			'comment_post_ID' => $post_id,
			'comment_author' => __CLASS__,
			'comment_content' => __FUNCTION__,
		));

		// Verify that we're getting the expected comment before we add logging comments
		$comments = get_comments();
		$this->assertCount( 1, $comments );
		$this->assertEquals( $comment_id, $comments[0]->comment_ID );


		$action_id = as_schedule_single_action( time(), 'a hook' );
		$logger = ActionScheduler::logger();
		$message = 'Logging that something happened';
		$log_id = $logger->log( $action_id, $message );


		// Verify that logging comments are excluded from general comment queries
		$comments = get_comments();
		$this->assertCount( 1, $comments );
		$this->assertEquals( $comment_id, $comments[0]->comment_ID );

		// Verify that logging comments are returned when asking for them specifically
		$comments = get_comments(array(
			'type' => ActionScheduler_wpCommentLogger::TYPE,
		));
		// Expecting two: one when the action is created, another when we added our custom log
		$this->assertCount( 2, $comments );
		$this->assertContains( $log_id, wp_list_pluck($comments, 'comment_ID'));
	}
}
 
