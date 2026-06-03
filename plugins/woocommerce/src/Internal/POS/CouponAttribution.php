<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Coupon;
use WP_Error;
use WP_REST_Request;

/**
 * Server-side handling of client-asserted POS coupon attribution and manager overrides.
 *
 * The mobile client adds POS context as flat scalar entries in the coupon's `meta_data`
 * array on POST /wc/v3/coupons:
 *
 *     "meta_data": [
 *       { "key": "_pos_staff_user_id",          "value": 42 },
 *       // Optional — only present when a manager authorized override of `create_coupons`:
 *       { "key": "_pos_override_staff_user_id", "value": 7  }
 *     ]
 *
 * Validation runs in the pre-insert filter so bogus attribution or override data
 * rolls back the entire request (HTTP 400, no DB row). After successful persistence,
 * audit lands in the WC log; coupons have no order-note timeline, so per-coupon audit
 * surfacing in wp-admin is deferred (the attribution meta itself remains on the coupon
 * for later wp-admin display work).
 *
 * Capability enforcement for the action itself is intentionally client-side in M1 —
 * the same trust model as {@see OrderAttribution}.
 *
 * @since 11.0.0
 * @internal
 */
class CouponAttribution implements RegisterHooksInterface {

	public const LOG_SOURCE = 'woocommerce-pos';

	/**
	 * Register the lifecycle hooks for shop_coupon.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_pre_insert_shop_coupon_object', array( $this, 'handle_pre_insert' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_coupon_object', array( $this, 'handle_post_insert' ), 10, 3 );
	}

	/**
	 * Handle the pre-insert filter for shop_coupon.
	 *
	 * @internal
	 *
	 * @param WC_Coupon|WP_Error $coupon_or_error The draft coupon being inserted, or
	 *                                            an upstream error to pass through.
	 * @param WP_REST_Request    $request         The incoming request.
	 * @param bool               $creating        Whether this is a create (true) or update (false).
	 * @return WC_Coupon|WP_Error
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function handle_pre_insert( $coupon_or_error, $request, $creating ) {
		unset( $request, $creating );

		if ( is_wp_error( $coupon_or_error ) ) {
			return $coupon_or_error;
		}

		if ( ! $coupon_or_error instanceof WC_Coupon ) {
			return $coupon_or_error;
		}

		$staff_user_id    = $this->read_int_meta( $coupon_or_error, OrderAttribution::META_KEY_STAFF_USER_ID );
		$override_user_id = $this->read_int_meta( $coupon_or_error, OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID );
		$has_any_pos_meta = $staff_user_id > 0 || $override_user_id > 0;

		if ( ! $has_any_pos_meta ) {
			return $coupon_or_error;
		}

		$attribution_error = $this->validate_staff_user( $staff_user_id );
		if ( is_wp_error( $attribution_error ) ) {
			return $attribution_error;
		}

		if ( $override_user_id > 0 ) {
			$override_error = $this->validate_override( $override_user_id, $staff_user_id );
			if ( is_wp_error( $override_error ) ) {
				return $override_error;
			}
		}

		return $coupon_or_error;
	}

	/**
	 * Handle the post-insert action for shop_coupon.
	 *
	 * Writes one log line per saved coupon. Coupons do not expose an order-note
	 * timeline, so per-coupon audit surfacing in wp-admin is deferred. The flat
	 * `_pos_staff_user_id` / `_pos_override_staff_user_id` meta persists on the coupon
	 * for a future wp-admin attribution UI to render.
	 *
	 * @internal
	 *
	 * @param WC_Coupon       $coupon   The freshly-saved coupon.
	 * @param WP_REST_Request $request  The incoming request.
	 * @param bool            $creating Whether this is a create (true) or update (false).
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function handle_post_insert( $coupon, $request, $creating ): void {
		unset( $request );

		if ( ! $coupon instanceof WC_Coupon ) {
			return;
		}

		$staff_user_id = $this->read_int_meta( $coupon, OrderAttribution::META_KEY_STAFF_USER_ID );
		if ( $staff_user_id <= 0 ) {
			return;
		}

		$staff_user = get_userdata( $staff_user_id );
		if ( ! $staff_user ) {
			wc_get_logger()->warning(
				sprintf(
					'POS coupon attribution skipped: user %d not found at post-insert time (coupon %d).',
					$staff_user_id,
					$coupon->get_id()
				),
				array( 'source' => self::LOG_SOURCE )
			);
			return;
		}

		$override_user_id = $this->read_int_meta( $coupon, OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID );

		if ( $override_user_id > 0 ) {
			wc_get_logger()->info(
				sprintf(
					'POS override on coupon %1$d by %2$s (ID %3$d), approved by user ID %4$d.',
					$coupon->get_id(),
					$staff_user->user_login,
					$staff_user->ID,
					$override_user_id
				),
				array( 'source' => self::LOG_SOURCE )
			);
		} else {
			$action = $creating ? 'created' : 'updated';
			wc_get_logger()->info(
				sprintf(
					'POS coupon %1$d %2$s by %3$s (ID %4$d).',
					$coupon->get_id(),
					$action,
					$staff_user->user_login,
					$staff_user->ID
				),
				array( 'source' => self::LOG_SOURCE )
			);
		}
	}

	/**
	 * Read an integer scalar from coupon meta. Returns 0 if absent or invalid.
	 *
	 * @param WC_Coupon $coupon The coupon.
	 * @param string    $key    The meta key.
	 * @return int
	 */
	private function read_int_meta( WC_Coupon $coupon, string $key ): int {
		$value = $coupon->get_meta( $key, true );
		if ( '' === $value || null === $value ) {
			return 0;
		}
		if ( ! is_numeric( $value ) ) {
			return 0;
		}
		return (int) $value;
	}

	/**
	 * Validate the staff (operator) user id.
	 *
	 * @param int $staff_user_id The asserted staff user id.
	 * @return true|WP_Error
	 */
	private function validate_staff_user( int $staff_user_id ) {
		if ( $staff_user_id <= 0 ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution requires a positive _pos_staff_user_id.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$user = get_userdata( $staff_user_id );
		if ( ! $user ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution references an unknown user.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! Capabilities::has_pos_access( $staff_user_id ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution user does not have POS access.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate the manager-override approver for a coupon action.
	 *
	 * @param int $override_user_id The asserted approver (manager) user id.
	 * @param int $staff_user_id    The operator user id (for self-override check).
	 * @return true|WP_Error
	 */
	private function validate_override( int $override_user_id, int $staff_user_id ) {
		if ( $override_user_id === $staff_user_id ) {
			return new WP_Error(
				'woocommerce_pos_self_override',
				__( 'POS override cannot be granted by the same user performing the action.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$approver = get_userdata( $override_user_id );
		if ( ! $approver ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override references an unknown approver.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! Capabilities::has_pos_access( $override_user_id ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override approver does not have POS access.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! Capabilities::user_has_pos_capability( $override_user_id, Capabilities::CAP_CREATE_COUPONS ) ) {
			return new WP_Error(
				'woocommerce_pos_override_forbidden',
				__( 'POS override approver does not hold the required capability.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}
}
