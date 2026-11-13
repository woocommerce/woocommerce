<?php
/**
 * Order delete ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\OrderAbilityTrait;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order delete ability.
 *
 * The delete runs on the write pipeline of ActionableAbility, so the
 * validators of the order resource can refuse it, the syncs see the deleted
 * or trashed order, and a failure rolls everything back. A forced delete
 * also deletes the orders that the order store deletes with it, such as
 * refunds and, with HPOS, subscriptions, so they go through the same checks.
 */
class OrderDelete extends ActionableAbility {

	use DomainAbilityHelpers;
	use OrderAbilityTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/order-delete';

	/**
	 * The result status of a forced delete, which leaves nothing to restore.
	 */
	private const STATUS_DELETED = 'deleted';

	/**
	 * ID of the order the call deletes, kept because the delete clears the object's ID.
	 *
	 * @var int
	 */
	private int $order_id = 0;

	/**
	 * Whether the call deletes for good instead of trashing.
	 *
	 * @var bool
	 */
	private bool $force = false;

	/**
	 * IDs of the orders that the order store deletes with a forced delete, as the validators saw them.
	 *
	 * @var int[]
	 */
	private array $child_ids = array();

	/**
	 * The order the call will delete or trash, and the orders that a forced delete takes with it.
	 *
	 * @var \WC_Abstract_Order[]
	 */
	private array $deleting = array();

	/**
	 * The order and the orders deleted with it, as they were before a forced delete.
	 *
	 * @var \WC_Abstract_Order[]
	 */
	private array $deleted = array();

	/**
	 * The order the call moved to the trash.
	 *
	 * @var \WC_Order[]
	 */
	private array $trashed = array();

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 11.4.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Delete order', 'woocommerce' ),
			'description'         => __(
				'Move an order to the trash, or delete it permanently.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::build_output_schema(),
			'permission_callback' => array( __CLASS__, 'can_delete_order' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * The resource the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'order';
	}

	/**
	 * A delete writes no extension fields.
	 *
	 * @return bool
	 */
	protected function writes_extension_fields(): bool {
		return false;
	}

	/**
	 * Load the order and check that it can be trashed when the call does not force a delete.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected function load( array $input ) {
		$this->order_id  = 0;
		$this->force     = $input['force'] ?? false;
		$this->child_ids = array();
		$this->deleting  = array();
		$this->deleted   = array();
		$this->trashed   = array();

		$order = self::get_order_from_input( $input );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		/**
		 * Filter whether an order supports trashing in WooCommerce domain abilities.
		 *
		 * @param bool      $supports_trash Whether the order supports trashing.
		 * @param \WC_Order $order          The order being considered for trashing.
		 *
		 * @since 11.4.0
		 */
		$supports_trash = apply_filters( 'woocommerce_order_object_trashable', EMPTY_TRASH_DAYS > 0, $order );

		if ( ! $this->force && ! $supports_trash ) {
			return new \WP_Error(
				'woocommerce_ability_order_trash_not_supported',
				__( 'Trash is disabled on this site. Pass force: true to permanently delete.', 'woocommerce' ),
				array( 'status' => 501 )
			);
		}

		$this->order_id = $order->get_id();

		return $order;
	}

	/**
	 * Nothing changes in memory, and the delete saves no other object.
	 *
	 * @param \WC_Order $subject Order to delete.
	 * @param array     $input   Ability input.
	 * @return null
	 */
	protected function change( $subject, array $input ) {
		unset( $input );

		$this->child_ids = $this->force ? self::get_child_ids( $subject ) : array();
		$this->deleting  = array_merge( array( $subject ), array_values( array_filter( array_map( 'wc_get_order', $this->child_ids ) ) ) );

		return null;
	}

	/**
	 * Delete or trash the order. A child order that appeared after the validators
	 * ran, or that survives the delete because a plugin stopped it, fails the call.
	 *
	 * @param \WC_Order $subject Order to delete.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		if ( $this->force && self::get_child_ids( $subject ) !== $this->child_ids ) {
			return new \WP_Error(
				'woocommerce_ability_order_children_changed',
				__( 'The refunds or child orders of this order changed during the delete. Try again.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		if ( $this->force ) {
			$this->deleted = array_map( static fn( \WC_Abstract_Order $order ) => clone $order, $this->deleting );
		} else {
			$this->trashed = array( clone $subject );
		}

		$deleted = $subject->delete( $this->force );
		$stored  = wc_get_order( $this->order_id );

		if (
			! $deleted
			|| ( $this->force && false !== $stored )
			|| ( ! $this->force && ( ! $stored instanceof \WC_Order || OrderStatus::TRASH !== $stored->get_status( 'edit' ) ) )
			|| array_filter( $this->child_ids, static fn( int $child_id ) => false !== wc_get_order( $child_id ) )
		) {
			return new \WP_Error(
				'woocommerce_ability_order_delete_failed',
				__( 'Failed to delete order.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return null;
	}

	/**
	 * IDs of the orders that the order store deletes with a forced delete of the order, sorted.
	 *
	 * The queries match what the order store does. HPOS deletes every child of
	 * an order type that is not hierarchical, and each deleted child deletes its
	 * own children the same way. The posts storage deletes the refunds of a shop order.
	 *
	 * @param \WC_Order $order Order to delete.
	 * @return int[]
	 */
	private static function get_child_ids( \WC_Order $order ): array {
		global $wpdb;

		$ids = array();
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$orders_table = OrdersTableDataStore::get_orders_table_name();
			$parents      = is_post_type_hierarchical( $order->get_type() ) ? array() : array( $order->get_id() );
			while ( ! empty( $parents ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $parents ), '%d' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- The table name comes from the data store, and the placeholders match the IDs.
				$children = $wpdb->get_results( $wpdb->prepare( "SELECT id, type FROM $orders_table WHERE parent_order_id IN ( $placeholders )", $parents ) );
				$parents  = array();
				foreach ( $children as $child ) {
					$child_id = (int) $child->id;
					if ( in_array( $child_id, $ids, true ) || $order->get_id() === $child_id ) {
						continue;
					}
					$ids[] = $child_id;
					if ( ! is_post_type_hierarchical( $child->type ) ) {
						$parents[] = $child_id;
					}
				}
			}
		} elseif ( 'shop_order' === $order->get_type() ) {
			$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $order->get_id() ) ) );
		}

		sort( $ids );

		return $ids;
	}

	/**
	 * The order and, for a forced delete, the orders deleted with it, for the validators.
	 *
	 * @return \WC_Abstract_Order[]
	 */
	protected function get_objects_to_delete(): array {
		return $this->deleting;
	}

	/**
	 * The order and the orders deleted with it, as they were before a forced delete, which clears the ID of the live objects.
	 *
	 * @return \WC_Abstract_Order[]
	 */
	protected function get_deleted_objects(): array {
		return $this->deleted;
	}

	/**
	 * The order, when the call trashed it.
	 *
	 * @return \WC_Order[]
	 */
	protected function get_trashed_objects(): array {
		return $this->trashed;
	}

	/**
	 * The response of a delete.
	 *
	 * @param \WC_Order $subject Deleted order.
	 * @return array
	 */
	protected function prepare_response( $subject ): array {
		unset( $subject );

		return array(
			'id'     => $this->order_id,
			'status' => $this->force ? self::STATUS_DELETED : OrderStatus::TRASH,
		);
	}

	/**
	 * Get the input schema.
	 *
	 * @return array
	 */
	private static function build_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'    => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'force' => array(
					'type'        => 'boolean',
					'description' => __(
						'Permanently delete the order. Defaults to false, which moves the order to trash.',
						'woocommerce'
					),
					'default'     => false,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the output schema.
	 *
	 * @return array
	 */
	private static function build_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'     => array(
					'type'        => 'integer',
					'description' => __( 'ID of the order.', 'woocommerce' ),
				),
				'status' => array(
					'type'        => 'string',
					'enum'        => array( OrderStatus::TRASH, self::STATUS_DELETED ),
					'description' => __( 'What happened to the order. "trash": the order was moved to the trash, and can be restored. "deleted": the order was permanently deleted, and cannot be restored.', 'woocommerce' ),
				),
			),
			'additionalProperties' => false,
		);
	}
}
