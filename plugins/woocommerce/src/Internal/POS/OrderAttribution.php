<?php
/**
 * OrderAttribution class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Actors\AccessProfileRegistry;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\StoreActors\ActorAccessRepository;
use Automattic\WooCommerce\Internal\StoreActors\ActorRepository;
use WC_Abstract_Order;
use WC_Order;
use WC_Order_Refund;
use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Server-side handling of client-asserted POS order attribution and manager
 * overrides against the store-actor model.
 *
 * The mobile client adds POS context as flat scalar entries in the order's
 * `meta_data` array on POST/PUT /wc/v3/orders and the refunds endpoint:
 *
 *     "meta_data": [
 *       { "key": "_wc_pos_staff_id",            "value": 42 },
 *       { "key": "_wc_pos_staff_name",          "value": "Alex Cashier" },
 *       // Optional — only present when a manager authorized an override:
 *       { "key": "_wc_pos_override_staff_id",   "value": 7 },
 *       { "key": "_wc_pos_override_staff_name", "value": "Morgan Manager" },
 *       { "key": "_wc_pos_override_reason",     "value": "refund_orders" }
 *     ]
 *
 * Validation runs in the pre-insert filter so bogus attribution or override
 * data rolls back the entire order request (HTTP 400, no DB row). After
 * successful persistence, a single order note is written:
 *
 *   - Without override: `POS: {action} by {display_name}.`
 *   - With override:    `POS override: {reason} granted to {actor}, approved by {approver}.`
 *
 * Trust model: the REST request is still authenticated as a WP user (typically
 * the device admin / shop_manager). The actor_id meta asserts who at the
 * terminal performed the action — the server verifies the actor exists, is
 * active, and (for overrides) the approver's access profile grants
 * `manager_approval`. The server does NOT block the underlying action even if
 * the override is malformed; it records who-approved-whom for audit.
 *
 * @internal Owned by the Point of Sale staff (actors) feature.
 * @since 10.9.0
 */
class OrderAttribution implements RegisterHooksInterface {

	public const META_KEY_STAFF_ID            = '_wc_pos_staff_id';
	public const META_KEY_STAFF_NAME          = '_wc_pos_staff_name';
	public const META_KEY_OVERRIDE_STAFF_ID   = '_wc_pos_override_staff_id';
	public const META_KEY_OVERRIDE_STAFF_NAME = '_wc_pos_override_staff_name';
	public const META_KEY_OVERRIDE_REASON     = '_wc_pos_override_reason';
	public const LOG_SOURCE                   = 'woocommerce-pos';

	/**
	 * Permission tags that may be granted via manager override. Each one
	 * corresponds to an action a cashier-tier actor would have
	 * `approval_required` for, and a manager-tier actor would have `allow`.
	 *
	 * @var string[]
	 */
	private const OVERRIDABLE_TAGS = array(
		AccessProfileRegistry::TAG_REFUND_ORDERS,
		AccessProfileRegistry::TAG_CREATE_CUSTOM_DISCOUNTS,
		AccessProfileRegistry::TAG_EXIT_POS,
	);

	/**
	 * @var ActorRepository
	 */
	private ActorRepository $actor_repo;

	/**
	 * @var ActorAccessRepository
	 */
	private ActorAccessRepository $access_repo;

	/**
	 * @var AccessProfileRegistry
	 */
	private AccessProfileRegistry $profiles;

	/**
	 * DI init.
	 *
	 * @internal
	 *
	 * @param ActorRepository       $actor_repo  Actor repository.
	 * @param ActorAccessRepository $access_repo Actor access repository.
	 * @param AccessProfileRegistry $profiles    Access profile registry.
	 * @return void
	 */
	final public function init(
		ActorRepository $actor_repo,
		ActorAccessRepository $access_repo,
		AccessProfileRegistry $profiles
	): void {
		$this->actor_repo  = $actor_repo;
		$this->access_repo = $access_repo;
		$this->profiles    = $profiles;
	}

	/**
	 * Register the lifecycle hooks for shop_order + shop_order_refund.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'handle_pre_insert' ), 10, 3 );
		add_filter( 'woocommerce_rest_pre_insert_shop_order_refund_object', array( $this, 'handle_pre_insert' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_order_object', array( $this, 'handle_post_insert' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_order_refund_object', array( $this, 'handle_post_insert' ), 10, 3 );
	}

	/**
	 * Pre-insert validation for shop_order / shop_order_refund.
	 *
	 * @param WC_Abstract_Order|WP_Error $order_or_error The draft order, or an upstream error.
	 * @param WP_REST_Request            $request        The incoming request.
	 * @param bool                       $creating       Create vs update.
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

		$actor_id          = $this->read_int_meta( $order_or_error, self::META_KEY_STAFF_ID );
		$override_actor_id = $this->read_int_meta( $order_or_error, self::META_KEY_OVERRIDE_STAFF_ID );
		$override_reason   = $this->read_string_meta( $order_or_error, self::META_KEY_OVERRIDE_REASON );
		$has_any_pos_meta  = $actor_id > 0 || $override_actor_id > 0 || '' !== $override_reason;
		$has_override_part = $override_actor_id > 0 || '' !== $override_reason;

		if ( ! $has_any_pos_meta ) {
			return $order_or_error;
		}

		$attribution_error = $this->validate_actor( $actor_id );
		if ( is_wp_error( $attribution_error ) ) {
			return $attribution_error;
		}

		if ( $has_override_part && ( $override_actor_id <= 0 || '' === $override_reason ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override requires both _wc_pos_override_staff_id and _wc_pos_override_reason.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $override_actor_id > 0 ) {
			$override_error = $this->validate_override( $override_actor_id, $override_reason, $actor_id );
			if ( is_wp_error( $override_error ) ) {
				return $override_error;
			}
		}

		return $order_or_error;
	}

	/**
	 * Post-insert: write one order note + one log line per saved order/refund.
	 *
	 * @param WC_Abstract_Order $order    Freshly-saved order or refund.
	 * @param WP_REST_Request   $request  Incoming request.
	 * @param bool              $creating Create vs update.
	 * @return void
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function handle_post_insert( $order, $request, $creating ): void {
		unset( $request );

		if ( ! $order instanceof WC_Abstract_Order ) {
			return;
		}

		$actor_id = $this->read_int_meta( $order, self::META_KEY_STAFF_ID );
		if ( $actor_id <= 0 ) {
			return;
		}

		$actor = $this->actor_repo->get_by_id( $actor_id );
		if ( null === $actor ) {
			wc_get_logger()->warning(
				sprintf(
					'POS attribution skipped: actor %d not found at post-insert time (order %d).',
					$actor_id,
					$order->get_id()
				),
				array( 'source' => self::LOG_SOURCE )
			);
			return;
		}

		$actor_name_snapshot = $this->read_string_meta( $order, self::META_KEY_STAFF_NAME );
		$actor_label         = '' !== $actor_name_snapshot ? $actor_name_snapshot : (string) $actor['display_name'];

		$is_refund   = $order instanceof WC_Order_Refund;
		$note_target = $is_refund ? wc_get_order( $order->get_parent_id() ) : $order;

		if ( ! $note_target instanceof WC_Order ) {
			return;
		}

		$override_actor_id = $this->read_int_meta( $order, self::META_KEY_OVERRIDE_STAFF_ID );
		$override_reason   = $this->read_string_meta( $order, self::META_KEY_OVERRIDE_REASON );

		if ( $override_actor_id > 0 && '' !== $override_reason ) {
			$override_name = $this->read_string_meta( $order, self::META_KEY_OVERRIDE_STAFF_NAME );
			$this->write_override_note( $note_target, $order, $actor_label, $override_actor_id, $override_name, $override_reason );
		} else {
			$this->write_attribution_note( $note_target, $order, $actor_label, $creating, $is_refund );
		}
	}

	/**
	 * Read an integer scalar from order meta. Returns 0 if absent or invalid.
	 *
	 * @param WC_Abstract_Order $order Order.
	 * @param string            $key   Meta key.
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
	 * @param WC_Abstract_Order $order Order.
	 * @param string            $key   Meta key.
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
	 * Validate the asserted actor.
	 *
	 * @param int $actor_id Asserted actor ID.
	 * @return true|WP_Error
	 */
	private function validate_actor( int $actor_id ) {
		if ( $actor_id <= 0 ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution requires a positive _wc_pos_staff_id.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$actor = $this->actor_repo->get_by_id( $actor_id );
		if ( null === $actor || ! empty( $actor['date_deleted_gmt'] ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution references an unknown staff member.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ActorRepository::STATUS_ACTIVE !== (string) $actor['status'] ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution staff member is not active.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$access = $this->access_repo->get_for_actor( $actor_id );
		if ( null === $access || ActorAccessRepository::STATUS_ACTIVE !== (string) ( $access['status'] ?? '' ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_attribution',
				__( 'POS attribution staff member has no active POS access.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate manager-override fields against the actor model + profile registry.
	 *
	 * @param int    $override_actor_id Approver actor ID.
	 * @param string $override_reason   Permission tag being elevated.
	 * @param int    $actor_id          Operator actor ID (for self-override check).
	 * @return true|WP_Error
	 */
	private function validate_override( int $override_actor_id, string $override_reason, int $actor_id ) {
		if ( ! in_array( $override_reason, self::OVERRIDABLE_TAGS, true ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override reason is not a supported overridable permission tag.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( $override_actor_id === $actor_id ) {
			return new WP_Error(
				'woocommerce_pos_self_override',
				__( 'POS override cannot be granted by the same staff member performing the action.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$approver = $this->actor_repo->get_by_id( $override_actor_id );
		if ( null === $approver || ! empty( $approver['date_deleted_gmt'] ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override references an unknown approver.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ActorRepository::STATUS_ACTIVE !== (string) $approver['status'] ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override approver is not active.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$approver_access = $this->access_repo->get_for_actor( $override_actor_id );
		if ( null === $approver_access || ActorAccessRepository::STATUS_ACTIVE !== (string) ( $approver_access['status'] ?? '' ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_override',
				__( 'POS override approver has no active POS access.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$approver_profile_key = (string) ( $approver_access['access_profile_key'] ?? '' );
		if ( AccessProfileRegistry::ACCESS_ALLOW !== $this->profiles->resolve( $approver_profile_key, AccessProfileRegistry::TAG_MANAGER_APPROVAL ) ) {
			return new WP_Error(
				'woocommerce_pos_override_forbidden',
				__( 'POS override approver is not permitted to grant approvals.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Write the simple attribution order note (no override case).
	 *
	 * @param WC_Order          $note_target Order to attach the note to.
	 * @param WC_Abstract_Order $order       Order/refund being attributed.
	 * @param string            $actor_label Display label for the actor.
	 * @param bool              $creating    Create vs update.
	 * @param bool              $is_refund   Whether $order is a refund.
	 * @return void
	 */
	private function write_attribution_note(
		WC_Order $note_target,
		WC_Abstract_Order $order,
		string $actor_label,
		bool $creating,
		bool $is_refund
	): void {
		$action_verb = $this->describe_action( $creating, $is_refund );

		$note_target->add_order_note(
			sprintf(
				/* translators: 1: action verb (created/updated/refunded), 2: actor display name. */
				__( 'POS: %1$s by %2$s.', 'woocommerce' ),
				$action_verb,
				$actor_label
			),
			0,
			false
		);

		wc_get_logger()->info(
			sprintf(
				'POS %1$s %2$d %3$s by actor %4$s.',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$action_verb,
				$actor_label
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Write the combined override order note (override case).
	 *
	 * @param WC_Order          $note_target       Order to attach the note to.
	 * @param WC_Abstract_Order $order             Order/refund being attributed.
	 * @param string            $actor_label       Operator actor display label.
	 * @param int               $override_actor_id Approver actor ID.
	 * @param string            $override_name     Approver actor display label (snapshot).
	 * @param string            $override_reason   Permission tag being elevated.
	 * @return void
	 */
	private function write_override_note(
		WC_Order $note_target,
		WC_Abstract_Order $order,
		string $actor_label,
		int $override_actor_id,
		string $override_name,
		string $override_reason
	): void {
		if ( '' === $override_name ) {
			$approver       = $this->actor_repo->get_by_id( $override_actor_id );
			$override_name  = null !== $approver ? (string) $approver['display_name'] : sprintf( 'actor #%d', $override_actor_id );
		}

		$note_target->add_order_note(
			sprintf(
				/* translators: 1: permission tag, 2: actor display name, 3: approver display name. */
				__( 'POS override: %1$s granted to %2$s, approved by %3$s.', 'woocommerce' ),
				$override_reason,
				$actor_label,
				$override_name
			),
			0,
			false
		);

		wc_get_logger()->info(
			sprintf(
				'POS override: %1$s on %2$s %3$d granted to %4$s, approved by %5$s (actor ID %6$d).',
				$override_reason,
				$order instanceof WC_Order_Refund ? 'refund' : 'order',
				$order->get_id(),
				$actor_label,
				$override_name,
				$override_actor_id
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}

	/**
	 * Human-readable action verb for the attribution note + log line.
	 *
	 * @param bool $creating  Create vs update.
	 * @param bool $is_refund Whether the object is a refund.
	 * @return string
	 */
	private function describe_action( bool $creating, bool $is_refund ): string {
		if ( $is_refund ) {
			return $creating ? 'refunded' : 'refund updated';
		}
		return $creating ? 'created' : 'updated';
	}
}
