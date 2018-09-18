<?php

/**
 * Class ActionScheduler_wpCommentLogger
 */
class ActionScheduler_wpCommentLogger extends ActionScheduler_Logger {
	const AGENT = 'ActionScheduler';
	const TYPE = 'action_log';

	/**
	 * @param string $action_id
	 * @param string $message
	 * @param DateTime $date
	 *
	 * @return string The log entry ID
	 */
	public function log( $action_id, $message, DateTime $date = NULL ) {
		if ( empty($date) ) {
			$date = as_get_datetime_object();
		} else {
			$date = clone $date;
		}
		$comment_id = $this->create_wp_comment( $action_id, $message, $date );
		return $comment_id;
	}

	protected function create_wp_comment( $action_id, $message, DateTime $date ) {
		$comment_date_gmt = $date->format('Y-m-d H:i:s');
		$date->setTimezone( ActionScheduler_TimezoneHelper::get_local_timezone() );
		$comment_data = array(
			'comment_post_ID' => $action_id,
			'comment_date' => $date->format('Y-m-d H:i:s'),
			'comment_date_gmt' => $comment_date_gmt,
			'comment_author' => self::AGENT,
			'comment_content' => $message,
			'comment_agent' => self::AGENT,
			'comment_type' => self::TYPE,
		);
		return wp_insert_comment($comment_data);
	}

	/**
	 * @param string $entry_id
	 *
	 * @return ActionScheduler_LogEntry
	 */
	public function get_entry( $entry_id ) {
		$comment = $this->get_comment( $entry_id );
		if ( empty($comment) || $comment->comment_type != self::TYPE ) {
			return new ActionScheduler_NullLogEntry();
		}

		$date = as_get_datetime_object( $comment->comment_date_gmt );
		$date->setTimezone( ActionScheduler_TimezoneHelper::get_local_timezone() );
		return new ActionScheduler_LogEntry( $comment->comment_post_ID, $comment->comment_content, $date );
	}

	/**
	 * @param string $action_id
	 *
	 * @return ActionScheduler_LogEntry[]
	 */
	public function get_logs( $action_id ) {
		$status = 'all';
		if ( get_post_status($action_id) == 'trash' ) {
			$status = 'post-trashed';
		}
		$comments = get_comments(array(
			'post_id' => $action_id,
			'orderby' => 'comment_date_gmt',
			'order' => 'ASC',
			'type' => self::TYPE,
			'status' => $status,
		));
		$logs = array();
		foreach ( $comments as $c ) {
			$entry = $this->get_entry( $c );
			if ( !empty($entry) ) {
				$logs[] = $entry;
			}
		}
		return $logs;
	}

	protected function get_comment( $comment_id ) {
		return get_comment( $comment_id );
	}



	/**
	 * @param WP_Comment_Query $query
	 */
	public function filter_comment_queries( $query ) {
		foreach ( array('ID', 'parent', 'post_author', 'post_name', 'post_parent', 'type', 'post_type', 'post_id', 'post_ID') as $key ) {
			if ( !empty($query->query_vars[$key]) ) {
				return; // don't slow down queries that wouldn't include action_log comments anyway
			}
		}
		$query->query_vars['action_log_filter'] = TRUE;
		add_filter( 'comments_clauses', array( $this, 'filter_comment_query_clauses' ), 10, 2 );
	}

	/**
	 * @param array $clauses
	 * @param WP_Comment_Query $query
	 *
	 * @return array
	 */
	public function filter_comment_query_clauses( $clauses, $query ) {
		if ( !empty($query->query_vars['action_log_filter']) ) {
			$clauses['where'] .= $this->get_where_clause();
		}
		return $clauses;
	}

	/**
	 * Make sure Action Scheduler logs are excluded from comment feeds, which use WP_Query, not
	 * the WP_Comment_Query class handled by @see self::filter_comment_queries().
	 *
	 * @param string $where
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public function filter_comment_feed( $where, $query ) {
		if ( is_comment_feed() ) {
			$where .= $this->get_where_clause();
		}
		return $where;
	}

	/**
	 * Return a SQL clause to exclude Action Scheduler comments.
	 *
	 * @return string
	 */
	protected function get_where_clause() {
		global $wpdb;
		return sprintf( " AND {$wpdb->comments}.comment_type != '%s'", self::TYPE );
	}

	/**
	 * Remove action log entries from wp_count_comments()
	 *
	 * @param array $stats
	 * @param int $post_id
	 *
	 * @return object
	 */
	public function filter_comment_count( $stats, $post_id ) {
		global $wpdb;

		if ( 0 === $post_id ) {
			$stats = $this->get_comment_count();
		}

		return $stats;
	}

	/**
	 * Retrieve the comment counts from our cache, or the database if the cached version isn't set.
	 *
	 * @return object
	 */
	protected function get_comment_count() {
		global $wpdb;

		$stats = get_transient( 'as_comment_count' );

		if ( ! $stats ) {
			$stats = array();

			$count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} WHERE comment_type NOT IN('order_note','action_log') GROUP BY comment_approved", ARRAY_A );

			$total = 0;
			$stats = array();
			$approved = array( '0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed' );

			foreach ( (array) $count as $row ) {
				// Don't count post-trashed toward totals
				if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] ) {
					$total += $row['num_comments'];
				}
				if ( isset( $approved[ $row['comment_approved'] ] ) ) {
					$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
				}
			}

			$stats['total_comments'] = $total;
			$stats['all']            = $total;

			foreach ( $approved as $key ) {
				if ( empty( $stats[ $key ] ) ) {
					$stats[ $key ] = 0;
				}
			}

			$stats = (object) $stats;
			set_transient( 'as_comment_count', $stats );
		}

		return $stats;
	}

	/**
	 * Delete comment count cache whenever there is new comment or the status of a comment changes. Cache
	 * will be regenerated next time ActionScheduler_wpCommentLogger::filter_comment_count() is called.
	 */
	public function delete_comment_count_cache() {
		delete_transient( 'as_comment_count' );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {
		add_action( 'action_scheduler_before_process_queue', array( $this, 'disable_comment_counting' ), 10, 0 );
		add_action( 'action_scheduler_after_process_queue', array( $this, 'enable_comment_counting' ), 10, 0 );
		add_action( 'action_scheduler_stored_action', array( $this, 'log_stored_action' ), 10, 1 );
		add_action( 'action_scheduler_canceled_action', array( $this, 'log_canceled_action' ), 10, 1 );
		add_action( 'action_scheduler_before_execute', array( $this, 'log_started_action' ), 10, 1 );
		add_action( 'action_scheduler_after_execute', array( $this, 'log_completed_action' ), 10, 1 );
		add_action( 'action_scheduler_failed_execution', array( $this, 'log_failed_action' ), 10, 2 );
		add_action( 'action_scheduler_failed_action', array( $this, 'log_timed_out_action' ), 10, 2 );
		add_action( 'action_scheduler_unexpected_shutdown', array( $this, 'log_unexpected_shutdown' ), 10, 2 );
		add_action( 'action_scheduler_reset_action', array( $this, 'log_reset_action' ), 10, 1 );
		add_action( 'pre_get_comments', array( $this, 'filter_comment_queries' ), 10, 1 );
		add_action( 'wp_count_comments', array( $this, 'filter_comment_count' ), 20, 2 ); // run after WC_Comments::wp_count_comments() to make sure we exclude order notes and action logs
		add_action( 'comment_feed_where', array( $this, 'filter_comment_feed' ), 10, 2 );

		// Delete comments count cache whenever there is a new comment or a comment status changes
		add_action( 'wp_insert_comment', array( $this, 'delete_comment_count_cache' ) );
		add_action( 'wp_set_comment_status', array( $this, 'delete_comment_count_cache' ) );
	}

	public function disable_comment_counting() {
		wp_defer_comment_counting(true);
	}
	public function enable_comment_counting() {
		wp_defer_comment_counting(false);
	}

	public function log_stored_action( $action_id ) {
		$this->log( $action_id, __('action created', 'action-scheduler') );
	}

	public function log_canceled_action( $action_id ) {
		$this->log( $action_id, __('action canceled', 'action-scheduler') );
	}

	public function log_started_action( $action_id ) {
		$this->log( $action_id, __('action started', 'action-scheduler') );
	}

	public function log_completed_action( $action_id ) {
		$this->log( $action_id, __('action complete', 'action-scheduler') );
	}

	public function log_failed_action( $action_id, Exception $exception ) {
		$this->log( $action_id, sprintf(__('action failed: %s', 'action-scheduler'), $exception->getMessage() ));
	}

	public function log_timed_out_action( $action_id, $timeout) {
		$this->log( $action_id, sprintf( __('action timed out after %s seconds', 'action-scheduler'), $timeout ) );
	}

	public function log_unexpected_shutdown( $action_id, $error ) {
		if ( !empty($error) ) {
			$this->log( $action_id, sprintf(__('unexpected shutdown: PHP Fatal error %s in %s on line %s', 'action-scheduler'), $error['message'], $error['file'], $error['line'] ) );
		}
	}

	public function log_reset_action( $action_id ) {
		$this->log( $action_id, __('action reset', 'action_scheduler') );
	}

}
