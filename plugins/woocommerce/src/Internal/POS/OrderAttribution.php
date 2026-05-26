<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Abstract_Order;
use WC_Order;
use WC_Order_Refund;
use WP_Error;
use WP_REST_Request;

/**
 * Server-side handling of client-asserted POS order attribution.
 *
 * The mobile client adds a `_pos_attribution` entry to the order's `meta_data` array
 * on POST/PUT /wc/v3/orders (and the equivalent refunds endpoint), shaped like:
 *
 *     { "staff_user_id": 42 }
 *
 * Validation runs in the pre-insert filter so a bogus attribution rolls back the entire
 * order request (HTTP 400, no DB row). After successful persistence, a system-flavored
 * order note attributes the action to the named user, and a WC_Logger line is emitted
 * for audit purposes.
 *
 * Attribution is client-asserted by design: every request still authenticates as the
 * device admin, capability enforcement on the underlying action is client-side. This
 * matches the trust model accepted in the i1 local-mode proposal.
 *
 * @since 10.9.0
 * @internal
 */
class OrderAttribution implements RegisterHooksInterface {

	public const META_KEY = '_pos_attribution';
	public const LOG_SOURCE = 'woocommerce-pos';

	/**
	 * Register the lifecycle hooks for shop_order + shop_order_refund.
	 *
	 * @since 10.9.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'handle_pre_insert' ), 10, 3 );
		add_filter( 'woocommerce_rest_pre_insert_shop_order_refund_object', array( $this, 'handle_pre_insert' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_order_object', array( $this, 'handle_post_insert' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_order_refund_object', array( $this, 'handle_post_insert' ), 10, 3 );
	}

	/**
	 * Handle the pre-insert filter for shop_order / shop_order_refund.
	 *
	 * Validates the `_pos_attribution` meta on the draft order. If the meta is present
	 * but malformed or references a non-existent user, returns a WP_Error to abort the
	 * entire REST request (no DB row is written).
	 *
	 * @internal
	 *
	 * @param WC_Abstract_Order|WP_Error $order_or_error The draft order being inserted, or
	 *                                                   an upstream error to pass through.
	 * @param WP_REST_Request            $request        The incoming request.
	 * @param bool                       $creating       Whether this is a create (true) or update (false).
	 * @return WC_Abstract_Order|WP_Error
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function handle_pre_insert( $order_or_error, $request, $creating ) {
		unset( $request, $creating );

		if ( is_wp_error( $order_or_error ) ) {
			return $order_or_error;
		}

		if ( ! $order_or_error instanceof WC_Abstract_Order ) {
			return $order_or_error;
		}

		$attribution = $order_or_error->get_meta( self::META_KEY, true );
		if ( empty( $attribution ) ) {
			return $order_or_error;
		}

		$validation = $this->validate_attribution( $attribution );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		return $order_or_error;
	}

	/**
	 * Handle the post-insert action for shop_order / shop_order_refund.
	 *
	 * Writes the order note + log line. Best-effort — if the user no longer exists by
	 * the time this runs (e.g. deleted between validation and persistence), the note
	 * is skipped and a warning is logged. Notes for refunds are attached to the parent
	 * order since refunds themselves don't expose add_order_note().
	 *
	 * @internal
	 *
	 * @param WC_Abstract_Order $order    The freshly-saved order or refund.
	 * @param WP_REST_Request   $request  The incoming request.
	 * @param bool              $creating Whether this is a create (true) or update (false).
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function handle_post_insert( $order, $request, $creating ): void {
		unset( $request );

		if ( ! $order instanceof WC_Abstract_Order ) {
			return;
		}

		$attribution = $order->get_meta( self::META_KEY, true );
		if ( ! is_array( $attribution ) || empty( $attribution['staff_user_id'] ) ) {
			return;
		}

		$staff_user_id = (int) $attribution['staff_user_id'];
		$user          = get_userdata( $staff_user_id );
		if ( ! $user ) {
			wc_get_logger()->warning(
				sprintf(
					'POS attribution skipped: user %d not found at post-insert time (order %d).',
					$staff_user_id,
					$order->get_id()
				),
				array( 'source' => self::LOG_SOURCE )
			);
			return;
		}

		$is_refund     = $order instanceof WC_Order_Refund;
		$note_target   = $is_refund ? wc_get_order( $order->get_parent_id() ) : $order;
		$action_verb   = $this->describe_action( $creating, $is_refund );

		if ( $note_target instanceof WC_Order ) {
			$note_target->add_order_note(
				sprintf(
					/* translators: 1: action verb describing the POS event, 2: user display name, 3: user login. */
					__( 'POS: %1$s by %2$s (%3$s).', 'woocommerce' ),
					$action_verb,
					$user->display_name,
					$user->user_login
				),
				0,
				false
			);
		}

		wc_get_logger()->info(
			sprintf(
				'POS %1$s %2$d %3$s by user %4$s (ID %5$d).',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$action_verb,
				$user->user_login,
				$staff_user_id
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Return the human-readable action verb for the order note + log line.
	 *
	 * @param bool $creating  Whether this is a create (true) or update (false).
	 * @param bool $is_refund Whether the object being processed is a refund.
	 * @return string
	 */
	private function describe_action( bool $creating, bool $is_refund ): string {
		if ( $is_refund ) {
			return $creating ? 'refunded' : 'refund updated';
		}

		return $creating ? 'created' : 'updated';
	}

	/**
	 * Validate the attribution payload structure and referenced user.
	 *
	 * @param mixed $attribution The value read from the order's `_pos_attribution` meta.
	 * @return true|WP_Error     True if valid, WP_Error describing the failure otherwise.
	 */
	private function validate_attribution( $attribution ) {
		if ( ! is_array( $attribution ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution must be an object with a staff_user_id field.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $attribution['staff_user_id'] ) || ! is_numeric( $attribution['staff_user_id'] ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution requires a numeric staff_user_id.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$staff_user_id = (int) $attribution['staff_user_id'];
		if ( $staff_user_id <= 0 ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution staff_user_id must be a positive integer.', 'woocommerce' ),
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

		if ( ! user_can( $staff_user_id, 'view_pos' ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution user does not have POS access.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}
}
