<?php

/**
 * Class ActionScheduler_DBStore
 *
 * Action data table data store.
 *
 * @since 3.0.0
 */
class ActionScheduler_DBStore extends ActionScheduler_Store {

	/** @var int */
	protected static $max_args_length = 8000;

	/** @var int */
	protected static $max_index_length = 191;

	/**
	 * Initialize the data store
	 *
	 * @codeCoverageIgnore
	 */
	public function init() {
		$table_maker = new ActionScheduler_StoreSchema();
		$table_maker->register_tables();
	}

	/**
	 * Save an action.
	 *
	 * @param ActionScheduler_Action $action Action object.
	 * @param DateTime               $date Optional schedule date. Default null.
	 *
	 * @return int Action ID.
	 */
	public function save_action( ActionScheduler_Action $action, \DateTime $date = null ) {
		try {

			$this->validate_action( $action );

			/** @var \wpdb $wpdb */
			global $wpdb;
			$data = [
				'hook'                 => $action->get_hook(),
				'status'               => ( $action->is_finished() ? self::STATUS_COMPLETE : self::STATUS_PENDING ),
				'scheduled_date_gmt'   => $this->get_scheduled_date_string( $action, $date ),
				'scheduled_date_local' => $this->get_scheduled_date_string_local( $action, $date ),
				'schedule'             => serialize( $action->get_schedule() ),
				'group_id'             => $this->get_group_id( $action->get_group() ),
			];
			$args = wp_json_encode( $action->get_args() );
			if ( strlen( $args ) <= static::$max_index_length ) {
				$data['args'] = $args;
			} else {
				$data['args']          = $this->hash_args( $args );
				$data['extended_args'] = $args;
			}

			$table_name = ! empty( $wpdb->actionscheduler_actions ) ? $wpdb->actionscheduler_actions : $wpdb->prefix . 'actionscheduler_actions';
			$wpdb->insert( $table_name, $data );
			$action_id = $wpdb->insert_id;

			if ( is_wp_error( $action_id ) ) {
				throw new RuntimeException( $action_id->get_error_message() );
			}
			elseif ( empty( $action_id ) ) {
				throw new RuntimeException( $wpdb->last_error ? $wpdb->last_error : __( 'Database error.', 'action-scheduler' ) );
			}

			do_action( 'action_scheduler_stored_action', $action_id );

			return $action_id;
		} catch ( \Exception $e ) {
			/* translators: %s: error message */
			throw new \RuntimeException( sprintf( __( 'Error saving action: %s', 'action-scheduler' ), $e->getMessage() ), 0 );
		}
	}

	/**
	 * Generate a hash from json_encoded $args using MD5 as this isn't for security.
	 *
	 * @param string $args JSON encoded action args.
	 * @return string
	 */
	protected function hash_args( $args ) {
		return md5( $args );
	}

	/**
	 * Get action args query param value from action args.
	 *
	 * @param array $args Action args.
	 * @return string
	 */
	protected function get_args_for_query( $args ) {
		$encoded = wp_json_encode( $args );
		if ( strlen( $encoded ) <= static::$max_index_length ) {
			return $encoded;
		}
		return $this->hash_args( $encoded );
	}
	/**
	 * Get a group's ID based on its name/slug.
	 *
	 * @param string $slug The string name of a group.
	 * @param bool $create_if_not_exists Whether to create the group if it does not already exist. Default, true - create the group.
	 *
	 * @return int The group's ID, if it exists or is created, or 0 if it does not exist and is not created.
	 */
	protected function get_group_id( $slug, $create_if_not_exists = true ) {
		if ( empty( $slug ) ) {
			return 0;
		}
		/** @var \wpdb $wpdb */
		global $wpdb;
		$group_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$wpdb->actionscheduler_groups} WHERE slug=%s", $slug ) );
		if ( empty( $group_id ) && $create_if_not_exists ) {
			$group_id = $this->create_group( $slug );
		}

		return $group_id;
	}

	/**
	 * Create an action group.
	 *
	 * @param string $slug Group slug.
	 *
	 * @return int Group ID.
	 */
	protected function create_group( $slug ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$wpdb->insert( $wpdb->actionscheduler_groups, [ 'slug' => $slug ] );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Retrieve an action.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @return ActionScheduler_Action
	 */
	public function fetch_action( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$data = $wpdb->get_row( $wpdb->prepare(
			"SELECT a.*, g.slug AS `group` FROM {$wpdb->actionscheduler_actions} a LEFT JOIN {$wpdb->actionscheduler_groups} g ON a.group_id=g.group_id WHERE a.action_id=%d",
			$action_id
		) );

		if ( empty( $data ) ) {
			return $this->get_null_action();
		}

		if ( ! empty( $data->extended_args ) ) {
			$data->args = $data->extended_args;
			unset( $data->extended_args );
		}

		try {
			$action = $this->make_action_from_db_record( $data );
		} catch ( ActionScheduler_InvalidActionException $exception ) {
			do_action( 'action_scheduler_failed_fetch_action', $action_id, $exception );
			return $this->get_null_action();
		}

		return $action;
	}

	/**
	 * Create a null action.
	 *
	 * @return ActionScheduler_NullAction
	 */
	protected function get_null_action() {
		return new ActionScheduler_NullAction();
	}

	/**
	 * Create an action from a database record.
	 *
	 * @param object $data Action database record.
	 *
	 * @return ActionScheduler_Action|ActionScheduler_CanceledAction|ActionScheduler_FinishedAction
	 */
	protected function make_action_from_db_record( $data ) {

		$hook     = $data->hook;
		$args     = json_decode( $data->args, true );
		$schedule = unserialize( $data->schedule );

		$this->validate_args( $args, $data->action_id );
		$this->validate_schedule( $schedule, $data->action_id );

		if ( empty( $schedule ) ) {
			$schedule = new ActionScheduler_NullSchedule();
		}
		$group = $data->group ? $data->group : '';

		return ActionScheduler::factory()->get_stored_action( $data->status, $data->hook, $args, $schedule, $group );
	}

	/**
	 * Find an action.
	 *
	 * @param string $hook Action hook.
	 * @param array  $params Parameters of the action to find.
	 *
	 * @return string|null ID of the next action matching the criteria or NULL if not found.
	 */
	public function find_action( $hook, $params = [] ) {
		$params = wp_parse_args( $params, [
			'args'   => null,
			'status' => self::STATUS_PENDING,
			'group'  => '',
		] );

		/** @var wpdb $wpdb */
		global $wpdb;
		$query = "SELECT a.action_id FROM {$wpdb->actionscheduler_actions} a";
		$args  = [];
		if ( ! empty( $params[ 'group' ] ) ) {
			$query  .= " INNER JOIN {$wpdb->actionscheduler_groups} g ON g.group_id=a.group_id AND g.slug=%s";
			$args[] = $params[ 'group' ];
		}
		$query  .= " WHERE a.hook=%s";
		$args[] = $hook;
		if ( ! is_null( $params[ 'args' ] ) ) {
			$query  .= " AND a.args=%s";
			$args[] = $this->get_args_for_query( $params[ 'args' ] );
		}

		$order = 'ASC';
		if ( ! empty( $params[ 'status' ] ) ) {
			$query  .= " AND a.status=%s";
			$args[] = $params[ 'status' ];

			if ( self::STATUS_PENDING == $params[ 'status' ] ) {
				$order = 'ASC'; // Find the next action that matches.
			} else {
				$order = 'DESC'; // Find the most recent action that matches.
			}
		}

		$query .= " ORDER BY scheduled_date_gmt $order LIMIT 1";

		$query = $wpdb->prepare( $query, $args );

		$id = $wpdb->get_var( $query );

		return $id;
	}

	/**
	 * Returns the SQL statement to query (or count) actions.
	 *
	 * @param array  $query Filtering options.
	 * @param string $select_or_count  Whether the SQL should select and return the IDs or just the row count.
	 *
	 * @return string SQL statement already properly escaped.
	 */
	protected function get_query_actions_sql( array $query, $select_or_count = 'select' ) {

		if ( ! in_array( $select_or_count, array( 'select', 'count' ) ) ) {
			throw new InvalidArgumentException( __( 'Invalid value for select or count parameter. Cannot query actions.', 'action-scheduler' ) );
		}

		$query = wp_parse_args( $query, [
			'hook'             => '',
			'args'             => null,
			'date'             => null,
			'date_compare'     => '<=',
			'modified'         => null,
			'modified_compare' => '<=',
			'group'            => '',
			'status'           => '',
			'claimed'          => null,
			'per_page'         => 5,
			'offset'           => 0,
			'orderby'          => 'date',
			'order'            => 'ASC',
		] );

		/** @var \wpdb $wpdb */
		global $wpdb;
		$sql  = ( 'count' === $select_or_count ) ? 'SELECT count(a.action_id)' : 'SELECT a.action_id';
		$sql .= " FROM {$wpdb->actionscheduler_actions} a";
		$sql_params = [];

		if ( ! empty( $query[ 'group' ] ) || 'group' === $query[ 'orderby' ] ) {
			$sql .= " LEFT JOIN {$wpdb->actionscheduler_groups} g ON g.group_id=a.group_id";
		}

		$sql .= " WHERE 1=1";

		if ( ! empty( $query[ 'group' ] ) ) {
			$sql          .= " AND g.slug=%s";
			$sql_params[] = $query[ 'group' ];
		}

		if ( $query[ 'hook' ] ) {
			$sql          .= " AND a.hook=%s";
			$sql_params[] = $query[ 'hook' ];
		}
		if ( ! is_null( $query[ 'args' ] ) ) {
			$sql          .= " AND a.args=%s";
			$sql_params[] = $this->get_args_for_query( $query[ 'args' ] );
		}

		if ( $query[ 'status' ] ) {
			$sql          .= " AND a.status=%s";
			$sql_params[] = $query[ 'status' ];
		}

		if ( $query[ 'date' ] instanceof \DateTime ) {
			$date = clone $query[ 'date' ];
			$date->setTimezone( new \DateTimeZone( 'UTC' ) );
			$date_string  = $date->format( 'Y-m-d H:i:s' );
			$comparator   = $this->validate_sql_comparator( $query[ 'date_compare' ] );
			$sql          .= " AND a.scheduled_date_gmt $comparator %s";
			$sql_params[] = $date_string;
		}

		if ( $query[ 'modified' ] instanceof \DateTime ) {
			$modified = clone $query[ 'modified' ];
			$modified->setTimezone( new \DateTimeZone( 'UTC' ) );
			$date_string  = $modified->format( 'Y-m-d H:i:s' );
			$comparator   = $this->validate_sql_comparator( $query[ 'modified_compare' ] );
			$sql          .= " AND a.last_attempt_gmt $comparator %s";
			$sql_params[] = $date_string;
		}

		if ( $query[ 'claimed' ] === true ) {
			$sql .= " AND a.claim_id != 0";
		} elseif ( $query[ 'claimed' ] === false ) {
			$sql .= " AND a.claim_id = 0";
		} elseif ( ! is_null( $query[ 'claimed' ] ) ) {
			$sql          .= " AND a.claim_id = %d";
			$sql_params[] = $query[ 'claimed' ];
		}

		if ( ! empty( $query['search'] ) ) {
			$sql .= " AND (a.hook LIKE %s OR (a.extended_args IS NULL AND a.args LIKE %s) OR a.extended_args LIKE %s";
			for( $i = 0; $i < 3; $i++ ) {
				$sql_params[] = sprintf( '%%%s%%', $query['search'] );
			}

			$search_claim_id = (int) $query['search'];
			if ( $search_claim_id ) {
				$sql .= ' OR a.claim_id = %d';
				$sql_params[] = $search_claim_id;
			}

			$sql .= ')';
		}

		if ( 'select' === $select_or_count ) {
			switch ( $query['orderby'] ) {
				case 'hook':
					$orderby = 'a.hook';
					break;
				case 'group':
					$orderby = 'g.slug';
					break;
				case 'modified':
					$orderby = 'a.last_attempt_gmt';
					break;
				case 'date':
				default:
					$orderby = 'a.scheduled_date_gmt';
					break;
			}
			if ( strtoupper( $query[ 'order' ] ) == 'ASC' ) {
				$order = 'ASC';
			} else {
				$order = 'DESC';
			}
			$sql .= " ORDER BY $orderby $order";
			if ( $query[ 'per_page' ] > 0 ) {
				$sql          .= " LIMIT %d, %d";
				$sql_params[] = $query[ 'offset' ];
				$sql_params[] = $query[ 'per_page' ];
			}
		}

		if ( ! empty( $sql_params ) ) {
			$sql = $wpdb->prepare( $sql, $sql_params );
		}

		return $sql;
	}

	/**
	 * Query for action count of list of action IDs.
	 *
	 * @param array  $query Query parameters.
	 * @param string $query_type Whether to select or count the results. Default, select.
	 *
	 * @return null|string|array The IDs of actions matching the query
	 */
	public function query_actions( $query = [], $query_type = 'select' ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$sql = $this->get_query_actions_sql( $query, $query_type );

		return ( 'count' === $query_type ) ? $wpdb->get_var( $sql ) : $wpdb->get_col( $sql );
	}

	/**
	 * Get a count of all actions in the store, grouped by status.
	 *
	 * @return array Set of 'status' => int $count pairs for statuses with 1 or more actions of that status.
	 */
	public function action_counts() {
		global $wpdb;

		$sql  = "SELECT a.status, count(a.status) as 'count'";
		$sql .= " FROM {$wpdb->actionscheduler_actions} a";
		$sql .= " GROUP BY a.status";

		$actions_count_by_status = array();
		$action_stati_and_labels = $this->get_status_labels();

		foreach ( $wpdb->get_results( $sql ) as $action_data ) {
			// Ignore any actions with invalid status
			if ( array_key_exists( $action_data->status, $action_stati_and_labels ) ) {
				$actions_count_by_status[ $action_data->status ] = $action_data->count;
			}
		}

		return $actions_count_by_status;
	}

	/**
	 * Cancel an action.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @return void
	 */
	public function cancel_action( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$updated = $wpdb->update(
			$wpdb->actionscheduler_actions,
			[ 'status' => self::STATUS_CANCELED ],
			[ 'action_id' => $action_id ],
			[ '%s' ],
			[ '%d' ]
		);
		if ( empty( $updated ) ) {
			/* translators: %s: action ID */
			throw new \InvalidArgumentException( sprintf( __( 'Unidentified action %s', 'action-scheduler' ), $action_id ) );
		}
		do_action( 'action_scheduler_canceled_action', $action_id );
	}

	/**
	 * Cancel pending actions by hook.
	 *
	 * @since 3.0.0
	 *
	 * @param string $hook Hook name.
	 *
	 * @return void
	 */
	public function cancel_actions_by_hook( $hook ) {
		$this->bulk_cancel_actions( [ 'hook' => $hook ] );
	}

	/**
	 * Cancel pending actions by group.
	 *
	 * @param string $group Group slug.
	 *
	 * @return void
	 */
	public function cancel_actions_by_group( $group ) {
		$this->bulk_cancel_actions( [ 'group' => $group ] );
	}

	/**
	 * Bulk cancel actions.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args Query parameters.
	 */
	protected function bulk_cancel_actions( $query_args ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		if ( ! is_array( $query_args ) ) {
			return;
		}

		// Don't cancel actions that are already canceled.
		if ( isset( $query_args['status'] ) && $query_args['status'] == self::STATUS_CANCELED ) {
			return;
		}

		$action_ids = true;
		$query_args = wp_parse_args(
			$query_args,
			[
				'per_page' => 1000,
				'status' => self::STATUS_PENDING,
			]
		);

		while ( $action_ids ) {
			$action_ids = $this->query_actions( $query_args );
			if ( empty( $action_ids ) ) {
				break;
			}

			$format     = array_fill( 0, count( $action_ids ), '%d' );
			$query_in   = '(' . implode( ',', $format ) . ')';
			$parameters = $action_ids;
			array_unshift( $parameters, self::STATUS_CANCELED );

			$wpdb->query(
				$wpdb->prepare( // wpcs: PreparedSQLPlaceholders replacement count ok.
					"UPDATE {$wpdb->actionscheduler_actions} SET status = %s WHERE action_id IN {$query_in}",
					$parameters
				)
			);

			do_action( 'action_scheduler_bulk_cancel_actions', $action_ids );
		}
	}

	/**
	 * Delete an action.
	 *
	 * @param int $action_id Action ID.
	 */
	public function delete_action( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$deleted = $wpdb->delete( $wpdb->actionscheduler_actions, [ 'action_id' => $action_id ], [ '%d' ] );
		if ( empty( $deleted ) ) {
			throw new \InvalidArgumentException( sprintf( __( 'Unidentified action %s', 'action-scheduler' ), $action_id ) );
		}
		do_action( 'action_scheduler_deleted_action', $action_id );
	}

	/**
	 * Get the schedule date for an action.
	 *
	 * @param string $action_id Action ID.
	 *
	 * @throws \InvalidArgumentException
	 * @return \DateTime The local date the action is scheduled to run, or the date that it ran.
	 */
	public function get_date( $action_id ) {
		$date = $this->get_date_gmt( $action_id );
		ActionScheduler_TimezoneHelper::set_local_timezone( $date );
		return $date;
	}

	/**
	 * Get the GMT schedule date for an action.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @throws \InvalidArgumentException
	 * @return \DateTime The GMT date the action is scheduled to run, or the date that it ran.
	 */
	protected function get_date_gmt( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->actionscheduler_actions} WHERE action_id=%d", $action_id ) );
		if ( empty( $record ) ) {
			throw new \InvalidArgumentException( sprintf( __( 'Unidentified action %s', 'action-scheduler' ), $action_id ) );
		}
		if ( $record->status == self::STATUS_PENDING ) {
			return as_get_datetime_object( $record->scheduled_date_gmt );
		} else {
			return as_get_datetime_object( $record->last_attempt_gmt );
		}
	}

	/**
	 * Stake a claim on actions.
	 *
	 * @param int       $max_actions Maximum number of action to include in claim.
	 * @param \DateTime $before_date Jobs must be schedule before this date. Defaults to now.
	 *
	 * @return ActionScheduler_ActionClaim
	 */
	public function stake_claim( $max_actions = 10, \DateTime $before_date = null, $hooks = array(), $group = '' ) {
		$claim_id = $this->generate_claim_id();
		$this->claim_actions( $claim_id, $max_actions, $before_date, $hooks, $group );
		$action_ids = $this->find_actions_by_claim_id( $claim_id );

		return new ActionScheduler_ActionClaim( $claim_id, $action_ids );
	}

	/**
	 * Generate a new action claim.
	 *
	 * @return int Claim ID.
	 */
	protected function generate_claim_id() {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$now = as_get_datetime_object();
		$wpdb->insert( $wpdb->actionscheduler_claims, [ 'date_created_gmt' => $now->format( 'Y-m-d H:i:s' ) ] );

		return $wpdb->insert_id;
	}

	/**
	 * Mark actions claimed.
	 *
	 * @param string    $claim_id Claim Id.
	 * @param int       $limit Number of action to include in claim.
	 * @param \DateTime $before_date Should use UTC timezone.
	 *
	 * @return int The number of actions that were claimed.
	 * @throws \RuntimeException
	 */
	protected function claim_actions( $claim_id, $limit, \DateTime $before_date = null, $hooks = array(), $group = '' ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$now  = as_get_datetime_object();
		$date = is_null( $before_date ) ? $now : clone $before_date;

		// can't use $wpdb->update() because of the <= condition
		$update = "UPDATE {$wpdb->actionscheduler_actions} SET claim_id=%d, last_attempt_gmt=%s, last_attempt_local=%s";
		$params = array(
			$claim_id,
			$now->format( 'Y-m-d H:i:s' ),
			current_time( 'mysql' ),
		);

		$where    = "WHERE claim_id = 0 AND scheduled_date_gmt <= %s AND status=%s";
		$params[] = $date->format( 'Y-m-d H:i:s' );
		$params[] = self::STATUS_PENDING;

		if ( ! empty( $hooks ) ) {
			$placeholders = array_fill( 0, count( $hooks ), '%s' );
			$where       .= ' AND hook IN (' . join( ', ', $placeholders ) . ')';
			$params       = array_merge( $params, array_values( $hooks ) );
		}

		if ( ! empty( $group ) ) {

			$group_id = $this->get_group_id( $group, false );

			// throw exception if no matching group found, this matches ActionScheduler_wpPostStore's behaviour
			if ( empty( $group_id ) ) {
				/* translators: %s: group name */
				throw new InvalidArgumentException( sprintf( __( 'The group "%s" does not exist.', 'action-scheduler' ), $group ) );
			}

			$where   .= ' AND group_id = %d';
			$params[] = $group_id;
		}

		$order    = "ORDER BY attempts ASC, scheduled_date_gmt ASC, action_id ASC LIMIT %d";
		$params[] = $limit;

		$sql = $wpdb->prepare( "{$update} {$where} {$order}", $params );

		$rows_affected = $wpdb->query( $sql );
		if ( $rows_affected === false ) {
			throw new \RuntimeException( __( 'Unable to claim actions. Database error.', 'action-scheduler' ) );
		}

		return (int) $rows_affected;
	}

	/**
	 * Get the number of active claims.
	 *
	 * @return int
	 */
	public function get_claim_count() {
		global $wpdb;

		$sql = "SELECT COUNT(DISTINCT claim_id) FROM {$wpdb->actionscheduler_actions} WHERE claim_id != 0 AND status IN ( %s, %s)";
		$sql = $wpdb->prepare( $sql, [ self::STATUS_PENDING, self::STATUS_RUNNING ] );

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Return an action's claim ID, as stored in the claim_id column.
	 *
	 * @param string $action_id Action ID.
	 * @return mixed
	 */
	public function get_claim_id( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$sql = "SELECT claim_id FROM {$wpdb->actionscheduler_actions} WHERE action_id=%d";
		$sql = $wpdb->prepare( $sql, $action_id );

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Retrieve the action IDs of action in a claim.
	 *
	 * @param string $claim_id Claim ID.
	 *
	 * @return int[]
	 */
	public function find_actions_by_claim_id( $claim_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$sql = "SELECT action_id FROM {$wpdb->actionscheduler_actions} WHERE claim_id=%d";
		$sql = $wpdb->prepare( $sql, $claim_id );

		$action_ids = $wpdb->get_col( $sql );

		return array_map( 'intval', $action_ids );
	}

	/**
	 * Release actions from a claim and delete the claim.
	 *
	 * @param ActionScheduler_ActionClaim $claim Claim object.
	 */
	public function release_claim( ActionScheduler_ActionClaim $claim ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$wpdb->update( $wpdb->actionscheduler_actions, [ 'claim_id' => 0 ], [ 'claim_id' => $claim->get_id() ], [ '%d' ], [ '%d' ] );
		$wpdb->delete( $wpdb->actionscheduler_claims, [ 'claim_id' => $claim->get_id() ], [ '%d' ] );
	}

	/**
	 * Remove the claim from an action.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @return void
	 */
	public function unclaim_action( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$wpdb->update(
			$wpdb->actionscheduler_actions,
			[ 'claim_id' => 0 ],
			[ 'action_id' => $action_id ],
			[ '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Mark an action as failed.
	 *
	 * @param int $action_id Action ID.
	 */
	public function mark_failure( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$updated = $wpdb->update(
			$wpdb->actionscheduler_actions,
			[ 'status' => self::STATUS_FAILED ],
			[ 'action_id' => $action_id ],
			[ '%s' ],
			[ '%d' ]
		);
		if ( empty( $updated ) ) {
			throw new \InvalidArgumentException( sprintf( __( 'Unidentified action %s', 'action-scheduler' ), $action_id ) );
		}
	}

	/**
	 * Add execution message to action log.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @return void
	 */
	public function log_execution( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$sql = "UPDATE {$wpdb->actionscheduler_actions} SET attempts = attempts+1, status=%s, last_attempt_gmt = %s, last_attempt_local = %s WHERE action_id = %d";
		$sql = $wpdb->prepare( $sql, self::STATUS_RUNNING, current_time( 'mysql', true ), current_time( 'mysql' ), $action_id );
		$wpdb->query( $sql );
	}

	/**
	 * Mark an action as complete.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @return void
	 */
	public function mark_complete( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$updated = $wpdb->update(
			$wpdb->actionscheduler_actions,
			[
				'status'             => self::STATUS_COMPLETE,
				'last_attempt_gmt'   => current_time( 'mysql', true ),
				'last_attempt_local' => current_time( 'mysql' ),
			],
			[ 'action_id' => $action_id ],
			[ '%s' ],
			[ '%d' ]
		);
		if ( empty( $updated ) ) {
			throw new \InvalidArgumentException( sprintf( __( 'Unidentified action %s', 'action-scheduler' ), $action_id ) );
		}
	}

	/**
	 * Get an action's status.
	 *
	 * @param int $action_id Action ID.
	 *
	 * @return string
	 */
	public function get_status( $action_id ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$sql    = "SELECT status FROM {$wpdb->actionscheduler_actions} WHERE action_id=%d";
		$sql    = $wpdb->prepare( $sql, $action_id );
		$status = $wpdb->get_var( $sql );

		if ( $status === null ) {
			throw new \InvalidArgumentException( __( 'Invalid action ID. No status found.', 'action-scheduler' ) );
		} elseif ( empty( $status ) ) {
			throw new \RuntimeException( __( 'Unknown status found for action.', 'action-scheduler' ) );
		} else {
			return $status;
		}
	}
}
