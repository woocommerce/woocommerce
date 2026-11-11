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

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce order delete ability.
 *
 * The delete runs on the write pipeline of ActionableAbility, so the
 * validators of the order resource can refuse it, the syncs see the deleted
 * or trashed order, and a failure rolls everything back.
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
	 * The order the call will delete or trash.
	 *
	 * @var \WC_Order[]
	 */
	private array $deleting = array();

	/**
	 * The order as it was before a forced delete.
	 *
	 * @var \WC_Order[]
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
		$this->order_id = 0;
		$this->force    = $input['force'] ?? false;
		$this->deleting = array();
		$this->deleted  = array();
		$this->trashed  = array();

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

		$this->deleting = array( $subject );

		return null;
	}

	/**
	 * Delete or trash the order. The data store removes or trashes its refunds with it.
	 *
	 * @param \WC_Order $subject Order to delete.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		$removed = array( clone $subject );
		if ( $this->force ) {
			$this->deleted = $removed;
		} else {
			$this->trashed = $removed;
		}

		$deleted = $subject->delete( $this->force );
		$stored  = wc_get_order( $this->order_id );

		if (
			! $deleted
			|| ( $this->force && false !== $stored )
			|| ( ! $this->force && ( ! $stored instanceof \WC_Order || OrderStatus::TRASH !== $stored->get_status( 'edit' ) ) )
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
	 * The order, for the validators.
	 *
	 * @return \WC_Order[]
	 */
	protected function get_objects_to_delete(): array {
		return $this->deleting;
	}

	/**
	 * The order as it was before a forced delete, which clears the ID of the live object.
	 *
	 * @return \WC_Order[]
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
