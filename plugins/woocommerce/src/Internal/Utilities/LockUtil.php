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
	 * @param int    $expiration The expiration time in minutes.
	 *
	 * @return bool True if the lock was set, false otherwise.
	 */
	public static function test_and_set_expiring( $lock_name, $expiration = 10 ) {
		global $wpdb;

		$expiration = (int) $expiration;

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options}
					SET option_value = IF(NOW() > CAST(option_value AS DATETIME), DATE_ADD(NOW(), INTERVAL %d MINUTE), option_value)
					WHERE option_name = %s;",
				$expiration,
				$lock_name
			)
		);
		return 1 === $result;
	}

	/**
	 * Unlock a lock.
	 *
	 * @param string $lock_name The name of the lock.
	 */
	public static function unlock( $lock_name ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options}
					SET option_value = 0
					WHERE option_name = %s;",
				$lock_name
			)
		);

	}
}


