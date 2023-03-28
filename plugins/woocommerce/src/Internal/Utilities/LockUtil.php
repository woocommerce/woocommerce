<?php

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Helper functions for working with locks.
 *
 * The locks are stored in the options table as a datetime value of the time the lock expires.
 * Thus, to check whether a lock is set, we need to check whether the current time is before the expiration time.
 *
 * To unlock a lock, we set the expiration time to 0.
 */
class LockUtil {

	/**
	 * Test and set a lock.
	 *
	 * @param string $lock_name The name of the lock.
	 * @param int    $expiration The expiration time in seconds.
	 *
	 * @return bool True if the lock was set, false otherwise.
	 */
	public static function test_and_set_expiring( $lock_name, $expiration = 10 * MINUTE_IN_SECONDS ) {
		global $wpdb;

		$expiration = (int) $expiration;

		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, DATE_ADD(NOW(), INTERVAL %d SECOND), 'no')
					ON DUPLICATE KEY UPDATE
						option_value = IF(NOW() > CAST(option_value AS DATETIME), DATE_ADD(NOW(), INTERVAL %d SECOND), option_value);",
				$lock_name,
				$expiration,
				$expiration
			)
		);
		/**  With ON DUPLICATE KEY UPDATE, the affected-rows value per row is 
		 * 1 if the row is inserted as a new row,
		 * 2 if an existing row is updated, and 
		 * 0 if an existing row is set to its current values.
		 */
		return $result > 0;
	}

	/**
	 * Unlock a lock.
	 * 
	 * Sets the expiration time to 1 second ago.
	 *
	 * @param string $lock_name The name of the lock.
	 */
	public static function unlock( $lock_name ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options}
					SET option_value = DATE_SUB(NOW(), INTERVAL 1 SECOND)
					WHERE option_name = %s;",
				$lock_name
			)
		);

	}
}


