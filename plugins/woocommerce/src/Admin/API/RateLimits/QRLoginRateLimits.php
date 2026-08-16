<?php
/**
 * Rate limiter for mobile app QR login endpoints.
 *
 * @package WooCommerce\Admin\API
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\API\RateLimits;

use Automattic\WooCommerce\Admin\API\MobileAppQRLogin;
use WC_Rate_Limiter;

defined( 'ABSPATH' ) || exit;

/**
 * Counter-based rate limiter for QR login endpoints.
 *
 * Uses WooCommerce's `wc_rate_limits` table and an atomic SQL upsert so
 * concurrent requests cannot bypass a bucket by racing a transient get/set
 * sequence.
 *
 * @internal
 */
class QRLoginRateLimits extends WC_Rate_Limiter {

	/**
	 * Generation bucket.
	 */
	const BUCKET_GENERATION = 'gen';

	/**
	 * Broad exchange-IP bucket.
	 */
	const BUCKET_EXCHANGE_IP = 'exc_ip';

	/**
	 * Invalid-token exchange bucket.
	 */
	const BUCKET_INVALID_EXCHANGE = 'exc_invalid';

	/**
	 * Invalid-token scan bucket.
	 */
	const BUCKET_INVALID_SCAN = 'scn_invalid';

	/**
	 * Valid-token exchange bucket.
	 */
	const BUCKET_VALID_EXCHANGE = 'exc_token';

	/**
	 * Status polling bucket.
	 */
	const BUCKET_STATUS = 'sta';

	/**
	 * Revoke endpoint bucket.
	 */
	const BUCKET_REVOKE = 'rev';

	/**
	 * Scan endpoint bucket.
	 */
	const BUCKET_SCAN = 'scn';

	/**
	 * Approval endpoint bucket.
	 */
	const BUCKET_APPROVE = 'apr';

	/**
	 * Session-status polling bucket.
	 */
	const BUCKET_SESSION_STATUS = 'ss';

	/**
	 * Prefix for QR login rate-limit rows.
	 */
	const KEY_PREFIX = 'qr_login_';

	/**
	 * Cache group.
	 */
	const CACHE_GROUP = 'wc_qr_login_rate_limit';

	/**
	 * Build the persisted rate-limit action ID.
	 *
	 * @param string $bucket Bucket name.
	 * @param string $identifier Bucket identifier.
	 * @return string
	 */
	public static function get_action_id( string $bucket, string $identifier ): string {
		$normalized_identifier = preg_replace( '/[^A-Za-z0-9:._-]/', '_', trim( $identifier ) );
		$normalized_identifier = is_string( $normalized_identifier ) && '' !== $normalized_identifier
			? $normalized_identifier
			: 'unknown';

		return substr( self::KEY_PREFIX . $bucket . '_' . $normalized_identifier, 0, 190 );
	}

	/**
	 * Consume one request from a bucket.
	 *
	 * @param string $bucket Bucket name.
	 * @param string $identifier Bucket identifier.
	 * @return bool True if the request is within the bucket limit.
	 */
	public static function consume( string $bucket, string $identifier ): bool {
		global $wpdb;

		$options = self::get_bucket_options( $bucket );
		if ( null === $options ) {
			return false;
		}

		$time              = time();
		$limit             = max( 1, (int) $options['limit'] );
		$rate_limit_expiry = $time + (int) $options['seconds'];
		$action_id         = self::get_action_id( $bucket, $identifier );

		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}wc_rate_limits
					(`rate_limit_key`, `rate_limit_expiry`, `rate_limit_remaining`)
				VALUES
					(%s, %d, %d)
				ON DUPLICATE KEY UPDATE
					`rate_limit_id` = IF(
						`rate_limit_expiry` < %d OR `rate_limit_remaining` > 0,
						LAST_INSERT_ID(`rate_limit_id`),
						LAST_INSERT_ID(0) + `rate_limit_id`
					),
					`rate_limit_remaining` = IF(
						`rate_limit_expiry` < %d,
						VALUES(`rate_limit_remaining`),
						IF(`rate_limit_remaining` > 0, `rate_limit_remaining` - 1, 0)
					),
					`rate_limit_expiry` = IF(`rate_limit_expiry` < %d, VALUES(`rate_limit_expiry`), `rate_limit_expiry`);
				",
				$action_id,
				$rate_limit_expiry,
				$limit - 1,
				$time,
				$time,
				$time
			)
		);

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->get_var( 'SELECT LAST_INSERT_ID()' ) > 0;
	}

	/**
	 * Get bucket options.
	 *
	 * @param string $bucket Bucket name.
	 * @return array{limit:int, seconds:int}|null
	 */
	private static function get_bucket_options( string $bucket ): ?array {
		switch ( $bucket ) {
			case self::BUCKET_GENERATION:
				return array(
					'limit'   => MobileAppQRLogin::MAX_TOKENS_PER_WINDOW,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_EXCHANGE_IP:
				return array(
					'limit'   => MobileAppQRLogin::MAX_EXCHANGE_IP_ATTEMPTS,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_INVALID_EXCHANGE:
				return array(
					'limit'   => MobileAppQRLogin::MAX_INVALID_EXCHANGE_ATTEMPTS,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_INVALID_SCAN:
				return array(
					'limit'   => MobileAppQRLogin::MAX_INVALID_SCAN_ATTEMPTS,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_VALID_EXCHANGE:
				return array(
					'limit'   => MobileAppQRLogin::MAX_EXCHANGE_ATTEMPTS,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_STATUS:
				return array(
					'limit'   => MobileAppQRLogin::MAX_STATUS_CHECKS_PER_WINDOW,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_REVOKE:
				return array(
					'limit'   => MobileAppQRLogin::MAX_REVOKE_ATTEMPTS,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_SCAN:
				return array(
					'limit'   => MobileAppQRLogin::MAX_SCAN_PER_WINDOW,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_APPROVE:
				return array(
					'limit'   => MobileAppQRLogin::MAX_APPROVE_PER_WINDOW,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
			case self::BUCKET_SESSION_STATUS:
				return array(
					'limit'   => MobileAppQRLogin::MAX_SESSION_STATUS_PER_WINDOW,
					'seconds' => 15 * MINUTE_IN_SECONDS,
				);
		}

		return null;
	}
}
