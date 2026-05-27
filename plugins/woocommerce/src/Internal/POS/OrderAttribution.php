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
 *       { "key": "_pos_staff_user_id",     "value": 42 },
 *       // Optional — only present when a manager authorized an override:
 *       { "key": "_pos_override_user_id",  "value": 7  },
 *       { "key": "_pos_override_reason",   "value": "refund_shop_orders" }
 *     ]
 *
 * Validation runs in the pre-insert filter so bogus attribution or override data
 * rolls back the entire order request (HTTP 400, no DB row). After successful
 * persistence, a single order note is written:
 *
 *   - Without override: `POS: {action} by {display_name} ({login}).`
 *   - With override:    `POS override: {reason} granted to {actor}, approved by {approver}.`
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
 * @since 10.9.0
 * @internal
 */
class OrderAttribution implements RegisterHooksInterface {

	public const META_KEY_STAFF_USER_ID    = '_pos_staff_user_id';
	public const META_KEY_OVERRIDE_USER_ID = '_pos_override_user_id';
	public const META_KEY_OVERRIDE_REASON  = '_pos_override_reason';
	public const LOG_SOURCE                = 'woocommerce-pos';

	/**
	 * Capabilities that may be granted via manager override.
	 *
	 * Order-scoped only in M1. Non-order overrides (e.g. opening POS settings)
	 * are tracked client-side until a follow-up milestone adds a dedicated audit
	 * endpoint.
	 */
	private const OVERRIDABLE_CAPABILITIES = array(
		'refund_shop_orders',
		'publish_shop_coupons',
	);

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

		$staff_user_id     = $this->read_int_meta( $order_or_error, self::META_KEY_STAFF_USER_ID );
		$override_user_id  = $this->read_int_meta( $order_or_error, self::META_KEY_OVERRIDE_USER_ID );
		$override_reason   = $this->read_string_meta( $order_or_error, self::META_KEY_OVERRIDE_REASON );
		$has_any_pos_meta  = $staff_user_id > 0 || $override_user_id > 0 || '' !== $override_reason;
		$has_override_part = $override_user_id > 0 || '' !== $override_reason;

		if ( ! $has_any_pos_meta ) {
			return $order_or_error;
		}

		// Attribution is required whenever any POS meta is present.
		$attribution_error = $this->validate_staff_user( $staff_user_id );
		if ( is_wp_error( $attribution_error ) ) {
			return $attribution_error;
		}

		// Override pair must appear together; reject partial sets.
		if ( $has_override_part && ( $override_user_id <= 0 || '' === $override_reason ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override requires both _pos_override_user_id and _pos_override_reason.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $override_user_id > 0 ) {
			$override_error = $this->validate_override( $override_user_id, $override_reason, $staff_user_id );
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

		$override_user_id = $this->read_int_meta( $order, self::META_KEY_OVERRIDE_USER_ID );
		$override_reason  = $this->read_string_meta( $order, self::META_KEY_OVERRIDE_REASON );

		if ( $override_user_id > 0 && '' !== $override_reason ) {
			$this->write_override_note( $note_target, $order, $staff_user, $override_user_id, $override_reason );
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
	 * Read a string scalar from order meta. Returns '' if absent or invalid.
	 *
	 * @param WC_Abstract_Order $order The order.
	 * @param string            $key   The meta key.
	 * @return string
	 */
	private function read_string_meta( WC_Abstract_Order $order, string $key ): string {
		$value = $order->get_meta( $key, true );
		if ( ! is_string( $value ) ) {
			return '';
		}
		return $value;
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

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered in WC_Install::create_roles() via POSCapabilities::pos_specific_capabilities().
		if ( ! user_can( $staff_user_id, 'view_pos' ) ) {
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
	 * @param string $override_reason  The capability the override elevates.
	 * @param int    $staff_user_id    The operator user id (for self-override check).
	 * @return true|WP_Error
	 */
	private function validate_override( int $override_user_id, string $override_reason, int $staff_user_id ) {
		if ( ! in_array( $override_reason, self::OVERRIDABLE_CAPABILITIES, true ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override reason is not a supported overridable capability.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

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

		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered in WC_Install::create_roles() via POSCapabilities::pos_specific_capabilities().
		if ( ! user_can( $override_user_id, 'view_pos' ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override approver does not have POS access.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ! user_can( $override_user_id, $override_reason ) ) {
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
		$action_verb = $this->describe_action( $creating, $is_refund );

		$note_target->add_order_note(
			sprintf(
				/* translators: 1: action verb (created/updated/refunded), 2: staff display name, 3: staff login. */
				__( 'POS: %1$s by %2$s (%3$s).', 'woocommerce' ),
				$action_verb,
				$staff_user->display_name,
				$staff_user->user_login
			),
			0,
			false
		);

		wc_get_logger()->info(
			sprintf(
				'POS %1$s %2$d %3$s by user %4$s (ID %5$d).',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$action_verb,
				$staff_user->user_login,
				$staff_user->ID
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Write the combined override order note (override case).
	 *
	 * Matches the prior POC's format: "POS override: {capability} granted to {actor},
	 * approved by {approver}." — a single line capturing both the attribution and
	 * the authorization, instead of two separate notes that would clutter the
	 * order timeline.
	 *
	 * @param WC_Order          $note_target      The order to attach the note to.
	 * @param WC_Abstract_Order $order            The actual order or refund object.
	 * @param \WP_User          $staff_user       The staff member who performed the action.
	 * @param int               $override_user_id The approver's user id.
	 * @param string            $override_reason  The capability being elevated.
	 */
	private function write_override_note(
		WC_Order $note_target,
		WC_Abstract_Order $order,
		\WP_User $staff_user,
		int $override_user_id,
		string $override_reason
	): void {
		$approver       = get_userdata( $override_user_id );
		$approver_label = $approver
			? sprintf( '%s (%s)', $approver->display_name, $approver->user_login )
			: sprintf( 'ID %d', $override_user_id );

		$note_target->add_order_note(
			sprintf(
				/* translators: 1: capability name, 2: actor display name + login, 3: approver display name + login. */
				__( 'POS override: %1$s granted to %2$s, approved by %3$s.', 'woocommerce' ),
				$override_reason,
				sprintf( '%s (%s)', $staff_user->display_name, $staff_user->user_login ),
				$approver_label
			),
			0,
			false
		);

		wc_get_logger()->info(
			sprintf(
				'POS override: %1$s on %2$s %3$d granted to %4$s (ID %5$d), approved by user ID %6$d.',
				$override_reason,
				$order instanceof WC_Order_Refund ? 'refund' : 'order',
				$order->get_id(),
				$staff_user->user_login,
				$staff_user->ID,
				$override_user_id
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Return the human-readable action verb for the attribution order note + log line.
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
