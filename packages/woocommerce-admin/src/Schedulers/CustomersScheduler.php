<?php
/**
 * Customer syncing related functions and actions.
 */

namespace Automattic\WooCommerce\Admin\Schedulers;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use \Automattic\WooCommerce\Admin\Schedulers\OrdersScheduler;

/**
 * CustomersScheduler Class.
 */
class CustomersScheduler extends ImportScheduler {
	/**
	 * Slug to identify the scheduler.
	 *
	 * @var string
	 */
	public static $name = 'customers';

	/**
	 * Attach customer lookup update hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_new_customer', array( __CLASS__, 'schedule_import' ) );
		add_action( 'woocommerce_update_customer', array( __CLASS__, 'schedule_import' ) );
		add_action( 'woocommerce_privacy_remove_order_personal_data', array( __CLASS__, 'schedule_anonymize' ) );
		add_action( 'delete_user', array( __CLASS__, 'schedule_user_delete' ) );

		CustomersDataStore::init();
		parent::init();
	}

	/**
	 * Add customer dependencies.
	 *
	 * @return array
	 */
	public static function get_dependencies() {
		return array(
			'delete_batch_init' => OrdersScheduler::get_action( 'delete_batch_init' ),
			'anonymize'         => self::get_action( 'import' ),
			'delete_user'       => self::get_action( 'import' ),
		);
	}

	/**
	 * Get the customer IDs and total count that need to be synced.
	 *
	 * @param int      $limit Number of records to retrieve.
	 * @param int      $page  Page number.
	 * @param int|bool $days Number of days prior to current date to limit search results.
	 * @param bool     $skip_existing Skip already imported customers.
	 */
	public static function get_items( $limit = 10, $page = 1, $days = false, $skip_existing = false ) {
		$customer_roles = apply_filters( 'woocommerce_analytics_import_customer_roles', array( 'customer' ) );
		$query_args     = array(
			'fields'   => 'ID',
			'orderby'  => 'ID',
			'order'    => 'ASC',
			'number'   => $limit,
			'paged'    => $page,
			'role__in' => $customer_roles,
		);

		if ( is_int( $days ) ) {
			$query_args['date_query'] = array(
				'after' => gmdate( 'Y-m-d 00:00:00', time() - ( DAY_IN_SECONDS * $days ) ),
			);
		}

		if ( $skip_existing ) {
			add_action( 'pre_user_query', array( __CLASS__, 'exclude_existing_customers_from_query' ) );
		}

		$customer_query = new \WP_User_Query( $query_args );

		remove_action( 'pre_user_query', array( __CLASS__, 'exclude_existing_customers_from_query' ) );

		return (object) array(
			'total' => $customer_query->get_total(),
			'ids'   => $customer_query->get_results(),
		);
	}

	/**
	 * Exclude users that already exist in our customer lookup table.
	 *
	 * Meant to be hooked into 'pre_user_query' action.
	 *
	 * @param WP_User_Query $wp_user_query WP_User_Query to modify.
	 */
	public static function exclude_existing_customers_from_query( $wp_user_query ) {
		global $wpdb;

		$wp_user_query->query_where .= " AND NOT EXISTS (
			SELECT ID FROM {$wpdb->prefix}wc_customer_lookup
			WHERE {$wpdb->prefix}wc_customer_lookup.user_id = {$wpdb->users}.ID
		)";
	}

	/**
	 * Get total number of rows imported.
	 *
	 * @return int
	 */
	public static function get_total_imported() {
		global $wpdb;
		return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_customer_lookup" );
	}

	/**
	 * Get all available scheduling actions.
	 * Used to determine action hook names and clear events.
	 *
	 * @return array
	 */
	public static function get_scheduler_actions() {
		$actions                = parent::get_scheduler_actions();
		$actions['anonymize']   = 'wc-admin_anonymize_' . static::$name;
		$actions['delete_user'] = 'wc-admin_delete_user_' . static::$name;
		return $actions;
	}

	/**
	 * Schedule import.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function schedule_import( $user_id ) {
		self::schedule_action( 'import', array( $user_id ) );
	}

	/**
	 * Schedule an action to anonymize a single Order.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public static function schedule_anonymize( $order ) {
		if ( is_a( $order, 'WC_Order' ) ) {
			// Postpone until any pending updates are completed.
			self::schedule_action( 'anonymize', array( $order->get_id() ) );
		}
	}

	/**
	 * Schedule an action to delete a single User.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function schedule_user_delete( $user_id ) {
		if ( (int) $user_id > 0 ) {
			// Postpone until any pending updates are completed.
			self::schedule_action( 'delete_user', array( $user_id ) );
		}
	}

	/**
	 * Imports a single customer.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function import( $user_id ) {
		CustomersDataStore::update_registered_customer( $user_id );
	}

	/**
	 * Delete a batch of customers.
	 *
	 * @param int $batch_size Number of items to delete.
	 * @return void
	 */
	public static function delete( $batch_size ) {
		global $wpdb;

		$customer_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT customer_id FROM {$wpdb->prefix}wc_customer_lookup ORDER BY customer_id ASC LIMIT %d",
				$batch_size
			)
		);

		foreach ( $customer_ids as $customer_id ) {
			CustomersDataStore::delete_customer( $customer_id );
		}
	}

	/**
	 * Anonymize the customer data for a single order.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public static function anonymize( $order_id ) {
		global $wpdb;

		$customer_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT customer_id FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $order_id )
		);

		if ( ! $customer_id ) {
			return;
		}

		// Long form query because $wpdb->update rejects [deleted].
		$deleted_text = __( '[deleted]', 'woocommerce' );
		$updated      = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_customer_lookup
					SET
						user_id = NULL,
						username = %s,
						first_name = %s,
						last_name = %s,
						email = %s,
						country = '',
						postcode = %s,
						city = %s,
						state = %s
					WHERE
						customer_id = %d",
				array(
					$deleted_text,
					$deleted_text,
					$deleted_text,
					'deleted@site.invalid',
					$deleted_text,
					$deleted_text,
					$deleted_text,
					$customer_id,
				)
			)
		);
		// If the customer row was anonymized, flush the cache.
		if ( $updated ) {
			ReportsCache::invalidate();
		}
	}

	/**
	 * Delete the customer data for a single user.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function delete_user( $user_id ) {
		CustomersDataStore::delete_customer_by_user_id( $user_id );
	}
}
