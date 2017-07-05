<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Allows the DB to be locked to avoid concurrent events doing the same thing.
 *
 * @since    3.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   Automattic
 */
class WC_Database_Locks {

	/**
	 * Request ID.
	 * @var string
	 */
	private static $request_id = '';

	/**
	 * Get ID of this request.
	 *
	 * @return string
	 */
	public static function get_request_id() {
		if ( ! self::$request_id ) {
			self::$request_id = uniqid( wp_rand(), true );
			add_action( 'shutdown', __CLASS__, 'release_all_locks' );
		}
		return self::$request_id;
	}

	/**
	 * Get an existing lock.
	 *
	 * @param  string $lock_name
	 * @return object|null
	 */
	public static function get_lock( $lock_name ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_locks WHERE name = %s;", $lock_name ) );
	}

	/**
	 * Create a lock.
	 *
	 * @param  string $lock_name The name of this unique lock.
	 * @param  int $release_timeout The duration in seconds to respect an existing lock. Default: 1 hour.
	 * @return bool False if a lock couldn't be created or if the lock is still valid. True otherwise.
	 */
	public static function create_lock( $lock_name, $release_timeout = 3600 ) {
		global $wpdb;

		if ( ! $release_timeout ) {
			$release_timeout = HOUR_IN_SECONDS;
		}

		$lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->prefix}woocommerce_locks ( `name`, `request_id`, `expires` ) VALUES ( %s, %s, %s );", $lock_name, self::get_request_id(), gmdate( 'Y-m-d H:i:s', time() + $release_timeout ) ) );

		if ( ! $lock_result ) {
			$lock_result = self::get_lock( $lock_name );

			// If a lock couldn't be created, and there isn't a lock, bail.
			if ( ! $lock_result ) {
				return false;
			}

			// Check to see if the lock is still valid. If it is, bail.
			if ( strtotime( $lock_result->expires ) > time() ) {
				return false;
			}

			// Lock is expired.
			self::release_lock( $lock_name );

			return self::create_lock( $lock_name, $release_timeout );
		}

		return true;
	}

	/**
	 * Aquire a lock, waiting for a set duration if needed.
	 *
	 * @param  string $lock_name The name of this unique lock.
	 * @param  int $max_wait_timeout The duration in seconds to wait for the lock to be freed.
	 * @return bool False if a lock couldn't be created or if the lock is still valid. True otherwise.
	 */
	public static function aquire_lock( $lock_name, $max_wait_timeout = 10 ) {
		global $wpdb;

		$start = time();

		while ( $start > ( time() - $max_wait_timeout ) ) {
			$locked = self::create_lock( $lock_name );

			if ( $locked ) {
				return true;
			}

			sleep( 1 );
		}

		return false;
	}

	/**
	 * Release a lock.
	 *
	 * @param  string $lock_name The name of this unique lock.
	 * @return bool True if the lock was successfully released. False on failure.
	 */
	public static function release_lock( $lock_name ) {
		global $wpdb;
		return (bool) $wpdb->delete( "{$wpdb->prefix}woocommerce_locks", array( 'name' => $lock_name ) );
	}

	/**
	 * Release all locks for a request.
	 */
	public static function release_all_locks() {
		global $wpdb;

		if ( self::$request_id ) {
			$wpdb->delete( "{$wpdb->prefix}woocommerce_locks", array( 'request_id' => self::$request_id ) );
		}
	}
}
