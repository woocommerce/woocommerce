<?php

use ActionScheduler_Store as Store;
use Action_Scheduler\Migration\Runner;
use Action_Scheduler\Migration\Config;
use Action_Scheduler\Migration\Controller;

/**
 * Class ActionScheduler_HybridStore
 *
 * A wrapper around multiple stores that fetches data from both.
 *
 * @since 3.0.0
 */
class ActionScheduler_HybridStore extends Store {
	const DEMARKATION_OPTION = 'action_scheduler_hybrid_store_demarkation';

	private $primary_store;
	private $secondary_store;
	private $migration_runner;

	/**
	 * @var int The dividing line between IDs of actions created
	 *          by the primary and secondary stores.
	 *
	 * Methods that accept an action ID will compare the ID against
	 * this to determine which store will contain that ID. In almost
	 * all cases, the ID should come from the primary store, but if
	 * client code is bypassing the API functions and fetching IDs
	 * from elsewhere, then there is a chance that an unmigrated ID
	 * might be requested.
	 */
	private $demarkation_id = 0;

	/**
	 * ActionScheduler_HybridStore constructor.
	 *
	 * @param Config $config Migration config object.
	 */
	public function __construct( Config $config = null ) {
		$this->demarkation_id = (int) get_option( self::DEMARKATION_OPTION, 0 );
		if ( empty( $config ) ) {
			$config = Controller::instance()->get_migration_config_object();
		}
		$this->primary_store    = $config->get_destination_store();
		$this->secondary_store  = $config->get_source_store();
		$this->migration_runner = new Runner( $config );
	}

	/**
	 * Initialize the table data store tables.
	 *
	 * @codeCoverageIgnore
	 */
	public function init() {
		add_action( 'action_scheduler/created_table', [ $this, 'set_autoincrement' ], 10, 2 );
		$this->primary_store->init();
		$this->secondary_store->init();
		remove_action( 'action_scheduler/created_table', [ $this, 'set_autoincrement' ], 10 );
	}

	/**
	 * When the actions table is created, set its autoincrement
	 * value to be one higher than the posts table to ensure that
	 * there are no ID collisions.
	 *
	 * @param string $table_name
	 * @param string $table_suffix
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function set_autoincrement( $table_name, $table_suffix ) {
		if ( ActionScheduler_StoreSchema::ACTIONS_TABLE === $table_suffix ) {
			if ( empty( $this->demarkation_id ) ) {
				$this->demarkation_id = $this->set_demarkation_id();
			}
			/** @var \wpdb $wpdb */
			global $wpdb;
			$wpdb->insert(
				$wpdb->{ActionScheduler_StoreSchema::ACTIONS_TABLE},
				[
					'action_id' => $this->demarkation_id,
					'hook'      => '',
					'status'    => '',
				]
			);
			$wpdb->delete(
				$wpdb->{ActionScheduler_StoreSchema::ACTIONS_TABLE},
				[ 'action_id' => $this->demarkation_id ]
			);
		}
	}

	/**
	 * Store the demarkation id in WP options.
	 *
	 * @param int $id The ID to set as the demarkation point between the two stores
	 *                Leave null to use the next ID from the WP posts table.
	 *
	 * @return int The new ID.
	 *
	 * @codeCoverageIgnore
	 */
	private function set_demarkation_id( $id = null ) {
		if ( empty( $id ) ) {
			/** @var \wpdb $wpdb */
			global $wpdb;
			$id = (int) $wpdb->get_var( "SELECT MAX(ID) FROM $wpdb->posts" );
			$id ++;
		}
		update_option( self::DEMARKATION_OPTION, $id );

		return $id;
	}

	/**
	 * Find the first matching action from the secondary store.
	 * If it exists, migrate it to the primary store immediately.
	 * After it migrates, the secondary store will logically contain
	 * the next matching action, so return the result thence.
	 *
	 * @param string $hook
	 * @param array  $params
	 *
	 * @return string
	 */
	public function find_action( $hook, $params = [] ) {
		$found_unmigrated_action = $this->secondary_store->find_action( $hook, $params );
		if ( ! empty( $found_unmigrated_action ) ) {
			$this->migrate( [ $found_unmigrated_action ] );
		}

		return $this->primary_store->find_action( $hook, $params );
	}

	/**
	 * Find actions matching the query in the secondary source first.
	 * If any are found, migrate them immediately. Then the secondary
	 * store will contain the canonical results.
	 *
	 * @param array $query
	 * @param string $query_type Whether to select or count the results. Default, select.
	 *
	 * @return int[]
	 */
	public function query_actions( $query = [], $query_type = 'select' ) {
		$found_unmigrated_actions = $this->secondary_store->query_actions( $query, 'select' );
		if ( ! empty( $found_unmigrated_actions ) ) {
			$this->migrate( $found_unmigrated_actions );
		}

		return $this->primary_store->query_actions( $query, $query_type );
	}

	/**
	 * Get a count of all actions in the store, grouped by status
	 *
	 * @return array Set of 'status' => int $count pairs for statuses with 1 or more actions of that status.
	 */
	public function action_counts() {
		$unmigrated_actions_count = $this->secondary_store->action_counts();
		$migrated_actions_count   = $this->primary_store->action_counts();
		$actions_count_by_status  = array();

		foreach ( $this->get_status_labels() as $status_key => $status_label ) {

			$count = 0;

			if ( isset( $unmigrated_actions_count[ $status_key ] ) ) {
				$count += $unmigrated_actions_count[ $status_key ];
			}

			if ( isset( $migrated_actions_count[ $status_key ] ) ) {
				$count += $migrated_actions_count[ $status_key ];
			}

			$actions_count_by_status[ $status_key ] = $count;
		}

		$actions_count_by_status = array_filter( $actions_count_by_status );

		return $actions_count_by_status;
	}

	/**
	 * If any actions would have been claimed by the secondary store,
	 * migrate them immediately, then ask the primary store for the
	 * canonical claim.
	 *
	 * @param int           $max_actions
	 * @param DateTime|null $before_date
	 *
	 * @return ActionScheduler_ActionClaim
	 */
	public function stake_claim( $max_actions = 10, DateTime $before_date = null, $hooks = array(), $group = '' ) {
		$claim = $this->secondary_store->stake_claim( $max_actions, $before_date, $hooks, $group );

		$claimed_actions = $claim->get_actions();
		if ( ! empty( $claimed_actions ) ) {
			$this->migrate( $claimed_actions );
		}

		$this->secondary_store->release_claim( $claim );

		return $this->primary_store->stake_claim( $max_actions, $before_date, $hooks, $group );
	}

	/**
	 * Migrate a list of actions to the table data store.
	 *
	 * @param array $action_ids List of action IDs.
	 */
	private function migrate( $action_ids ) {
		$this->migration_runner->migrate_actions( $action_ids );
	}

	/**
	 * Save an action to the primary store.
	 *
	 * @param ActionScheduler_Action $action Action object to be saved.
	 * @param DateTime               $date Optional. Schedule date. Default null.
	 */
	public function save_action( ActionScheduler_Action $action, DateTime $date = null ) {
		return $this->primary_store->save_action( $action, $date );
	}

	/**
	 * Retrieve an existing action whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function fetch_action( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			return $this->secondary_store->fetch_action( $action_id );
		} else {
			return $this->primary_store->fetch_action( $action_id );
		}
	}

	/**
	 * Cancel an existing action whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function cancel_action( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			$this->secondary_store->cancel_action( $action_id );
		} else {
			$this->primary_store->cancel_action( $action_id );
		}
	}

	/**
	 * Delete an existing action whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function delete_action( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			$this->secondary_store->delete_action( $action_id );
		} else {
			$this->primary_store->delete_action( $action_id );
		}
	}

	/**
	 * Get the schedule date an existing action whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function get_date( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			return $this->secondary_store->get_date( $action_id );
		} else {
			return $this->primary_store->get_date( $action_id );
		}
	}

	/**
	 * Mark an existing action as failed whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function mark_failure( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			$this->secondary_store->mark_failure( $action_id );
		} else {
			$this->primary_store->mark_failure( $action_id );
		}
	}

	/**
	 * Log the execution of an existing action whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function log_execution( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			$this->secondary_store->log_execution( $action_id );
		} else {
			$this->primary_store->log_execution( $action_id );
		}
	}

	/**
	 * Mark an existing action complete whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function mark_complete( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			$this->secondary_store->mark_complete( $action_id );
		} else {
			$this->primary_store->mark_complete( $action_id );
		}
	}

	/**
	 * Get an existing action status whether migrated or not.
	 *
	 * @param int $action_id Action ID.
	 */
	public function get_status( $action_id ) {
		if ( $action_id < $this->demarkation_id ) {
			return $this->secondary_store->get_status( $action_id );
		} else {
			return $this->primary_store->get_status( $action_id );
		}
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * All claim-related functions should operate solely
	 * on the primary store.
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Get the claim count from the table data store.
	 */
	public function get_claim_count() {
		return $this->primary_store->get_claim_count();
	}

	/**
	 * Retrieve the claim ID for an action from the table data store.
	 *
	 * @param int $action_id Action ID.
	 */
	public function get_claim_id( $action_id ) {
		return $this->primary_store->get_claim_id( $action_id );
	}

	/**
	 * Release a claim in the table data store.
	 *
	 * @param ActionScheduler_ActionClaim $claim Claim object.
	 */
	public function release_claim( ActionScheduler_ActionClaim $claim ) {
		$this->primary_store->release_claim( $claim );
	}

	/**
	 * Release claims on an action in the table data store.
	 *
	 * @param int $action_id Action ID.
	 */
	public function unclaim_action( $action_id ) {
		$this->primary_store->unclaim_action( $action_id );
	}

	/**
	 * Retrieve a list of action IDs by claim.
	 *
	 * @param int $claim_id Claim ID.
	 */
	public function find_actions_by_claim_id( $claim_id ) {
		return $this->primary_store->find_actions_by_claim_id( $claim_id );
	}
}
