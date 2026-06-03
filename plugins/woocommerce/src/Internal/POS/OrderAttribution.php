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
 * Server-side handling of client-asserted POS order attribution and manager overrides.
 *
 * The mobile client adds POS context as flat scalar entries in the order's `meta_data`
 * array on POST/PUT /wc/v3/orders (and the equivalent refunds endpoint):
 *
 *     "meta_data": [
 *       { "key": "_pos_staff_user_id",          "value": 42 },
 *       // Optional — only present when a manager authorized an override on a refund:
 *       { "key": "_pos_override_staff_user_id", "value": 7  }
 *     ]
 *
 * Which action the override authorizes is implied by the endpoint, not carried as a
 * separate meta value — refunds require `issue_refunds` on the approver, and the
 * order endpoint does not accept overrides (since `process_sales` is universal).
 *
 * Validation runs in the pre-insert filter so bogus attribution or override data
 * rolls back the entire request (HTTP 400, no DB row). After successful persistence,
 * a single order note is written:
 *
 *   - Without override: `POS: {action} by {display_name} ({login}).`
 *   - With override:    `POS override: {action} by {actor}, approved by {approver}.`
 *
 * The single-combined-note format on override matches the prior POC; a separate
 * attribution note alongside the override note would clutter the order timeline.
 *
 * Capability enforcement for the elevated action itself is intentionally
 * client-side in M1 — every mobile request still authenticates as the device
 * admin. The server records who-overrode-whom for audit, but does not block the
 * underlying action even if the override is malformed. This matches the trust
 * model accepted in the i1 local-mode proposal.
 *
 * @since 11.0.0
 * @internal
 */
class OrderAttribution implements RegisterHooksInterface {

	public const META_KEY_STAFF_USER_ID          = '_pos_staff_user_id';
	public const META_KEY_OVERRIDE_STAFF_USER_ID = '_pos_override_staff_user_id';
	public const LOG_SOURCE                      = 'woocommerce-pos';

	/**
	 * Register the lifecycle hooks for shop_order + shop_order_refund.
	 *
	 * @since 11.0.0
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
	 * Validates the POS attribution + override meta on the draft order. If any
	 * required check fails, returns a WP_Error to abort the entire REST request.
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

		$staff_user_id    = $this->read_int_meta( $order_or_error, self::META_KEY_STAFF_USER_ID );
		$override_user_id = $this->read_int_meta( $order_or_error, self::META_KEY_OVERRIDE_STAFF_USER_ID );
		$has_any_pos_meta = $staff_user_id > 0 || $override_user_id > 0;

		if ( ! $has_any_pos_meta ) {
			return $order_or_error;
		}

		// Attribution is required whenever any POS meta is present.
		$attribution_error = $this->validate_staff_user( $staff_user_id );
		if ( is_wp_error( $attribution_error ) ) {
			return $attribution_error;
		}

		if ( $override_user_id > 0 ) {
			// Plain orders do not accept overrides: `process_sales` is universal across POS roles,
			// so there is no scenario in M1 where an order create/update needs a manager override.
			if ( ! $order_or_error instanceof WC_Order_Refund ) {
				return new WP_Error(
					'woocommerce_pos_invalid_override',
					__( 'POS override is not supported on order creation or updates.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$override_error = $this->validate_override( $override_user_id, Capabilities::CAP_ISSUE_REFUNDS, $staff_user_id );
			if ( is_wp_error( $override_error ) ) {
				return $override_error;
			}
		}

		return $order_or_error;
	}

	/**
	 * Handle the post-insert action for shop_order / shop_order_refund.
	 *
	 * Writes one order note + one log line per saved order/refund. When an override
	 * is present, the note is the combined `POS override: ...` form; otherwise it's
	 * the simple attribution form. Notes on refunds attach to the parent order
	 * since refunds themselves don't expose add_order_note().
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

		$staff_user_id = $this->read_int_meta( $order, self::META_KEY_STAFF_USER_ID );
		if ( $staff_user_id <= 0 ) {
			return;
		}

		$staff_user = get_userdata( $staff_user_id );
		if ( ! $staff_user ) {
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

		$is_refund   = $order instanceof WC_Order_Refund;
		$note_target = $is_refund ? wc_get_order( $order->get_parent_id() ) : $order;

		if ( ! $note_target instanceof WC_Order ) {
			return;
		}

		$override_user_id = $this->read_int_meta( $order, self::META_KEY_OVERRIDE_STAFF_USER_ID );

		if ( $override_user_id > 0 ) {
			$this->write_override_note( $note_target, $order, $staff_user, $override_user_id, $is_refund );
		} else {
			$this->write_attribution_note( $note_target, $order, $staff_user, $creating, $is_refund );
		}
	}

	/**
	 * Read an integer scalar from order meta. Returns 0 if absent or invalid.
	 *
	 * @param WC_Abstract_Order $order The order.
	 * @param string            $key   The meta key.
	 * @return int
	 */
	private function read_int_meta( WC_Abstract_Order $order, string $key ): int {
		$value = $order->get_meta( $key, true );
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
	 * Validate the manager-override fields.
	 *
	 * @param int    $override_user_id The asserted approver (manager) user id.
	 * @param string $required_cap     The POS capability the approver must hold for this action.
	 * @param int    $staff_user_id    The operator user id (for self-override check).
	 * @return true|WP_Error
	 */
	private function validate_override( int $override_user_id, string $required_cap, int $staff_user_id ) {
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

		if ( ! Capabilities::user_has_pos_capability( $override_user_id, $required_cap ) ) {
			return new WP_Error(
				'woocommerce_pos_override_forbidden',
				__( 'POS override approver does not hold the required capability.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Write the simple attribution order note (no override case).
	 *
	 * @param WC_Order          $note_target The order to attach the note to (parent for refunds).
	 * @param WC_Abstract_Order $order       The actual order or refund object (for id / type).
	 * @param \WP_User          $staff_user  The staff member who performed the action.
	 * @param bool              $creating    Whether this was a create (vs update).
	 * @param bool              $is_refund   Whether the object being attributed is a refund.
	 */
	private function write_attribution_note(
		WC_Order $note_target,
		WC_Abstract_Order $order,
		\WP_User $staff_user,
		bool $creating,
		bool $is_refund
	): void {
		$note_target->add_order_note(
			$this->build_attribution_note( $staff_user, $creating, $is_refund ),
			0,
			false
		);

		wc_get_logger()->info(
			sprintf(
				'POS %1$s %2$d %3$s by user %4$s (ID %5$d).',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$this->describe_action( $creating, $is_refund ),
				$staff_user->user_login,
				$staff_user->ID
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Build the localized attribution note body. Each variant is its own full
	 * sentence so translators get an intact, grammatically-correct string instead
	 * of having to splice an English verb into a translated template.
	 *
	 * @param \WP_User $staff_user The staff member who performed the action.
	 * @param bool     $creating   Whether this was a create (vs update).
	 * @param bool     $is_refund  Whether the object being attributed is a refund.
	 * @return string
	 */
	private function build_attribution_note( \WP_User $staff_user, bool $creating, bool $is_refund ): string {
		if ( $is_refund ) {
			$template = $creating
				/* translators: 1: staff display name, 2: staff login. */
				? __( 'POS: refunded by %1$s (%2$s).', 'woocommerce' )
				/* translators: 1: staff display name, 2: staff login. */
				: __( 'POS: refund updated by %1$s (%2$s).', 'woocommerce' );
		} else {
			$template = $creating
				/* translators: 1: staff display name, 2: staff login. */
				? __( 'POS: created by %1$s (%2$s).', 'woocommerce' )
				/* translators: 1: staff display name, 2: staff login. */
				: __( 'POS: updated by %1$s (%2$s).', 'woocommerce' );
		}

		return sprintf( $template, $staff_user->display_name, $staff_user->user_login );
	}

	/**
	 * Write the combined override order note (override case).
	 *
	 * Single-line format capturing both the attribution and the authorization,
	 * instead of two separate notes that would clutter the order timeline. The
	 * action label is derived from the object type — refunds are the only override
	 * surface today, but the shape generalises to future overridable order actions.
	 *
	 * @param WC_Order          $note_target      The order to attach the note to.
	 * @param WC_Abstract_Order $order            The actual order or refund object.
	 * @param \WP_User          $staff_user       The staff member who performed the action.
	 * @param int               $override_user_id The approver's user id.
	 * @param bool              $is_refund        Whether the override applies to a refund.
	 */
	private function write_override_note(
		WC_Order $note_target,
		WC_Abstract_Order $order,
		\WP_User $staff_user,
		int $override_user_id,
		bool $is_refund
	): void {
		$approver       = get_userdata( $override_user_id );
		$approver_label = $approver
			? sprintf( '%s (%s)', $approver->display_name, $approver->user_login )
			: sprintf( 'ID %d', $override_user_id );

		$action_label = $is_refund
			? __( 'refund', 'woocommerce' )
			: __( 'order action', 'woocommerce' );

		$note_target->add_order_note(
			sprintf(
				/* translators: 1: action label (e.g. "refund"), 2: actor display name + login, 3: approver display name + login. */
				__( 'POS override: %1$s by %2$s, approved by %3$s.', 'woocommerce' ),
				$action_label,
				sprintf( '%s (%s)', $staff_user->display_name, $staff_user->user_login ),
				$approver_label
			),
			0,
			false
		);

		wc_get_logger()->info(
			sprintf(
				'POS override on %1$s %2$d by %3$s (ID %4$d), approved by user ID %5$d.',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$staff_user->user_login,
				$staff_user->ID,
				$override_user_id
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * English action verb for the log line. The order-note copy uses full localized
	 * sentences via build_attribution_note() instead, so this verb is log-only.
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
}
