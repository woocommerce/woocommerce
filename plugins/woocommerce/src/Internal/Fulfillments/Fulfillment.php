<?php
/**
 * WooCommerce order fulfillments.
 *
 * The WooCommerce order fulfillments class gets contains fulfillment related properties and methods.
 *
 * @package WooCommerce\Classes
 * @version 9.9.0
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use WC_Meta_Data;

defined( 'ABSPATH' ) || exit;

/**
 * WC Order Fulfillment Class
 *
 * @since 10.1.0
 */
class Fulfillment extends \WC_Data {
	/**
	 * Fulfillment constructor. Loads fulfillment data.
	 *
	 * @param array|string|Fulfillment $data Fulfillment data.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		if ( $data instanceof Fulfillment ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( absint( $data ) );
		} elseif ( is_array( $data ) && isset( $data['id'] ) ) {
			$this->set_id( absint( $data['id'] ) );
		} elseif ( is_string( $data ) && ! empty( $data ) ) {
			$this->set_id( absint( $data ) );
		} elseif ( is_object( $data ) && isset( $data->id ) ) {
			$this->set_id( absint( $data->id ) );
		} else {
			$this->set_object_read( true );
		}

		// Load the items array.
		$this->data_store = wc_get_container()->get( FulfillmentsDataStore::class );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Get the fulfillment ID.
	 *
	 * @return int Fulfillment ID.
	 */
	public function get_id(): int {
		return $this->data['fulfillment_id'] ?? 0;
	}

	/**
	 * Set the fulfillment ID.
	 *
	 * @param int $id Fulfillment ID.
	 */
	public function set_id( $id ): void {
		$this->data['fulfillment_id'] = is_numeric( $id ) ? absint( $id ) : 0;
		parent::set_id( $this->data['fulfillment_id'] );
	}

	/**
	 * Get the entity type.
	 *
	 * @return string|null Entity type.
	 */
	public function get_entity_type(): ?string {
		return $this->data['entity_type'] ?? null;
	}

	/**
	 * Set the entity type.
	 *
	 * @param class-string|null $entity_type Entity type.
	 */
	public function set_entity_type( ?string $entity_type ): void {
		$this->data['entity_type'] = $entity_type;
	}

	/**
	 * Get the entity ID.
	 *
	 * @return string|null Entity ID.
	 */
	public function get_entity_id(): ?string {
		return $this->data['entity_id'] ?? null;
	}

	/**
	 * Set the entity ID.
	 *
	 * @param string|null $entity_id Entity ID.
	 */
	public function set_entity_id( ?string $entity_id ): void {
		$this->data['entity_id'] = $entity_id;
	}

	/**
	 * Set fulfillment status.
	 *
	 * @param string|null $status Fulfillment status.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException If the status is invalid.
	 */
	public function set_status( ?string $status ): void {
		$statuses = FulfillmentUtils::get_fulfillment_statuses();
		if ( ! isset( $statuses[ $status ] ) ) {
			// Change the status to an existing one if the provided status is not valid.
			$status = $this->get_is_fulfilled() ? 'fulfilled' : 'unfulfilled';
		}
		// Set the fulfillment status.
		$this->set_is_fulfilled( $statuses[ $status ]['is_fulfilled'] ?? false );
		// Set the status in the data array.
		$this->data['status'] = $status;
	}

	/**
	 * Get the fulfillment status.
	 *
	 * @return string|null Fulfillment status.
	 */
	public function get_status(): ?string {
		return $this->data['status'] ?? null;
	}

	/**
	 * Set if the fulfillment is fulfilled. This is an internal method which is bound to the fulfillment status.
	 *
	 * @param bool $is_fulfilled Whether the fulfillment is fulfilled.
	 *
	 *  @return void
	 */
	private function set_is_fulfilled( bool $is_fulfilled ): void {
		$this->data['is_fulfilled'] = $is_fulfilled;
	}

	/**
	 * Get if the fulfillment is fulfilled.
	 *
	 * @return bool Whether the fulfillment is fulfilled.
	 */
	public function get_is_fulfilled(): bool {
		return $this->data['is_fulfilled'] ?? false;
	}

	/**
	 * Check if the fulfillment is locked.
	 *
	 * @return bool Whether the fulfillment is locked.
	 */
	public function is_locked(): bool {
		return boolval( $this->get_meta( '_is_locked' ) );
	}

	/**
	 * Get the lock message.
	 *
	 * @return string Lock message.
	 */
	public function get_lock_message(): string {
		return $this->get_meta( '_lock_message' ) ?? '';
	}

	/**
	 * Set the lock status and message.
	 *
	 * @param bool   $locked  Whether the fulfillment is locked.
	 * @param string $message Optional. The lock message.
	 *                        Defaults to an empty string.
	 *
	 * @return void
	 */
	public function set_locked( bool $locked, string $message = '' ): void {
		$this->update_meta_data( '_is_locked', $locked );
		if ( $locked ) {
			$this->update_meta_data( '_lock_message', $message );
		} else {
			$this->delete_meta_data( '_lock_message' );
		}
	}

	/**
	 * Get the date updated.
	 *
	 * @return string|null Date updated.
	 */
	public function get_date_updated(): ?string {
		return $this->data['date_updated'] ?? null;
	}

	/**
	 * Set the date updated.
	 *
	 * @param string|null $date_updated Date updated.
	 */
	public function set_date_updated( ?string $date_updated ): void {
		$this->data['date_updated'] = $date_updated;
	}

	/**
	 * Get the date the fulfillment was fulfilled.
	 */
	public function get_date_fulfilled(): ?string {
		return $this->meta_exists( '_date_fulfilled' ) ? $this->get_meta( '_date_fulfilled', true ) : null;
	}

	/**
	 * Set the date the fulfillment was fulfilled.
	 *
	 * @param string $date_fulfilled Date fulfilled.
	 */
	public function set_date_fulfilled( string $date_fulfilled ): void {
		$this->add_meta_data( '_date_fulfilled', $date_fulfilled, true );
	}

	/**
	 * Get the date deleted.
	 *
	 * @return string|null Date deleted.
	 */
	public function get_date_deleted(): ?string {
		return $this->data['date_deleted'] ?? null;
	}

	/**
	 * Set the date deleted.
	 *
	 * @param string|null $date_deleted Date deleted.
	 * @return void
	 */
	public function set_date_deleted( ?string $date_deleted ): void {
		$this->data['date_deleted'] = $date_deleted;
	}

	/**
	 * Get the fulfillment items.
	 *
	 * @return array Fulfillment items.
	 */
	public function get_items(): array {
		$items = $this->get_meta( '_items' );
		return $items ? $items : array();
	}

	/**
	 * Set the fulfillment items.
	 *
	 * @param array $items Fulfillment items.
	 */
	public function set_items( array $items ): void {
		$this->update_meta_data( '_items', array_values( $items ) );
	}

	/**
	 * Get the order associated with this fulfillment.
	 *
	 * This method retrieves the order based on the entity type and entity ID.
	 * If the entity type is `WC_Order`, it returns the order object.
	 *
	 * @return \WC_Order|null The order object or null if not found.
	 */
	public function get_order(): ?\WC_Order {
		$entity_type = $this->get_entity_type();
		$entity_id   = $this->get_entity_id();

		if ( ! $entity_type || ! $entity_id ) {
			return null;
		}

		if ( \WC_Order::class === $entity_type ) {
			$order = wc_get_order( (int) $entity_id );
			if ( $order instanceof \WC_Order ) {
				return $order;
			}
		}

		return null;
	}

	/**
	 * Returns all data for this object as an associative array.
	 *
	 * @return array
	 */
	public function get_raw_data() {
		return array_merge( array( 'id' => $this->get_id() ), $this->data, array( 'meta_data' => $this->get_raw_meta_data() ) );
	}

	/**
	 * Returns the meta data as array for this object.
	 *
	 * @return array
	 */
	public function get_raw_meta_data() {
		return array_map( fn( WC_Meta_Data $meta ) => (array) $meta->get_data(), $this->get_meta_data() );
	}
}
