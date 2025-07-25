<?php
/**
 * Class FulfillmentsDataStore file.
 *
 * @package WooCommerce\DataStores
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;
use WC_Meta_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Product Data Store
 *
 * @version  9.9.0
 */
class FulfillmentsDataStore extends \WC_Data_Store_WP implements \WC_Object_Data_Store_Interface, FulfillmentsDataStoreInterface {

	/**
	 * Method to create a new fulfillment in the database.
	 *
	 * @param Fulfillment $data The fulfillment object to create.
	 *
	 * @return void
	 *
	 * @throws \Exception If the fulfillment data is invalid.
	 * @throws \Exception If the fulfillment can't be created.
	 */
	public function create( &$data ): void {
		// Validate the fulfillment data.
		if ( ! $data->get_entity_type() ) {
			throw new \Exception( esc_html__( 'Invalid entity type.', 'woocommerce' ) );
		}
		if ( ! $data->get_entity_id() ) {
			throw new \Exception( esc_html__( 'Invalid entity ID.', 'woocommerce' ) );
		}
		if ( ! FulfillmentUtils::is_valid_fulfillment_status( $data->get_status() ) ) {
			throw new \Exception( esc_html__( 'Invalid fulfillment status.', 'woocommerce' ) );
		}

		$this->validate_items( $data );

		// Set fulfillment properties.
		$data->set_date_updated( current_time( 'mysql' ) );

		/**
		 * Filter to modify the fulfillment data before it is created.
		 *
		 * @since 10.1.0
		 */
		$data = apply_filters( 'woocommerce_fulfillment_before_create', $data );

		$is_fulfill_action = $data->get_is_fulfilled();
		// If the fulfillment is fulfilled, set the fulfilled date.
		if ( $is_fulfill_action ) {
			$data->set_date_fulfilled( current_time( 'mysql' ) );

			/**
			 * Filter to modify the fulfillment data before it is fulfilled.
			 *
			 * @since 10.1.0
			 */
			$data = apply_filters(
				'woocommerce_fulfillment_before_fulfill',
				$data
			);
		}

		// Save the fulfillment to the database.
		global $wpdb;
		$rows_inserted = $wpdb->insert(
			$wpdb->prefix . 'wc_order_fulfillments',
			array(
				'entity_type'  => $data->get_entity_type(),
				'entity_id'    => $data->get_entity_id(),
				'status'       => $data->get_status() ?? 'unfulfilled',
				'is_fulfilled' => $data->get_is_fulfilled() ? 1 : 0,
				'date_updated' => $data->get_date_updated(),
				'date_deleted' => $data->get_date_deleted(),
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		// Check for errors.
		if ( false === $rows_inserted ) {
			throw new \Exception( esc_html__( 'Failed to insert fulfillment.', 'woocommerce' ) );
		}

		// Set the ID of the fulfillment object.
		$data_id = $wpdb->insert_id;

		$data->set_id( $data_id );

		// If the fulfillment is fulfilled, set the fulfilled date.
		if ( $data->get_is_fulfilled() ) {
			$data->set_date_fulfilled( current_time( 'mysql' ) );
		}

		// Save the metadata for the fulfillment to the database.
		$data->save_meta_data();

		// Apply changes let's the object know that the current object reflects the database and no "changes" exist between the two.
		$data->apply_changes();
		$data->set_object_read( true );

		if ( ! doing_action( 'woocommerce_fulfillment_after_create' ) ) {
			/**
			* Action to perform after a fulfillment is created.
			*
			* @param Fulfillment $data The fulfillment object that was created.
			*
			* @since 10.1.0
			*/
			do_action( 'woocommerce_fulfillment_after_create', $data );
		}

		if ( $is_fulfill_action && ! doing_action( 'woocommerce_fulfillment_after_fulfill' ) ) {
			/**
			 * Action to perform after a fulfillment is fulfilled.
			 *
			 * @since 10.1.0
			 */
			do_action( 'woocommerce_fulfillment_after_fulfill', $data );
		}
	}

	/**
	 * Method to read a fulfillment from the database.
	 *
	 * @param Fulfillment $data The fulfillment object to read.
	 *
	 * @return void
	 *
	 * @throws \Exception If the fulfillment data can't be read.
	 */
	public function read( &$data ): void {
		// Read the fulfillment from the database.
		global $wpdb;

		$data_id          = $data->get_id();
		$fulfillment_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillments WHERE fulfillment_id = %d",
				$data_id
			),
			ARRAY_A
		);

		if ( empty( $fulfillment_data ) ) {
			throw new \Exception( esc_html__( 'Fulfillment not found.', 'woocommerce' ) );
		}

		$data->set_props( $fulfillment_data );
		$data->read_meta_data( true );
		$data->set_object_read( true );
	}

	/**
	 * Method to update an existing fulfillment in the database.
	 *
	 * @param Fulfillment $data The fulfillment object to update.
	 *
	 * @return void
	 *
	 * @throws \Exception If the fulfillment can't be updated.
	 */
	public function update( &$data ): void {
		// If the fulfillment is deleted, do nothing.
		if ( $data->get_date_deleted() ) {
			return;
		}

		// Update the fulfillment in the database.
		$data_id = $data->get_id();

		if ( ! FulfillmentUtils::is_valid_fulfillment_status( $data->get_status() ) ) {
			throw new \Exception( esc_html__( 'Invalid fulfillment status.', 'woocommerce' ) );
		}

		$this->validate_items( $data );

		/**
		 * Filter to modify the fulfillment data before it is updated.
		 *
		 * @param Fulfillment $data The fulfillment object that is being updated.
		 *
		 * @since 10.1.0
		 */
		$data = apply_filters( 'woocommerce_fulfillment_before_update', $data );

		// If the fulfillment is fulfilled, set the fulfilled date.
		$is_fulfill_action = false;
		if ( $data->get_is_fulfilled() && empty( $data->get_date_fulfilled() ) ) {
			$is_fulfill_action = true;
			$data->set_date_fulfilled( current_time( 'mysql' ) );

			/**
			 * Filter to modify the fulfillment data before it is fulfilled.
			 *
			 * @param Fulfillment $data The fulfillment object that is being fulfilled.
			 *
			 * @since 10.1.0
			 */
			$data = apply_filters(
				'woocommerce_fulfillment_before_fulfill',
				$data
			);
		}

		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'wc_order_fulfillments',
			array(
				'entity_type'  => $data->get_entity_type(),
				'entity_id'    => $data->get_entity_id(),
				'status'       => $data->get_status(),
				'is_fulfilled' => $data->get_is_fulfilled() ? 1 : 0,
				'date_updated' => current_time( 'mysql' ),
				'date_deleted' => $data->get_date_deleted(),
			),
			array(
				'fulfillment_id' => $data_id,
				'date_deleted'   => null,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		// Check for errors.
		if ( $wpdb->last_error ) {
			throw new \Exception( esc_html__( 'Failed to update fulfillment.', 'woocommerce' ) );
		}

		// If the fulfillment is fulfilled, set the fulfilled date.
		if ( $data->get_is_fulfilled() && ! $data->meta_exists( '_fulfilled_date' ) ) {
			$data->set_date_fulfilled( current_time( 'mysql' ) );
		}

		// Update the metadata for the fulfillment.
		$data->save_meta_data();
		$data->apply_changes();

		$data->set_object_read( true );

		if ( ! doing_action( 'woocommerce_fulfillment_after_update' ) ) {
			/**
			 * Action to perform after a fulfillment is updated.
			 *
			 * @param Fulfillment $data The fulfillment object that was updated.
			 *
			 * @since 10.1.0
			 */
			do_action( 'woocommerce_fulfillment_after_update', $data );
		}

		if ( $is_fulfill_action && ! doing_action( 'woocommerce_fulfillment_after_fulfill' ) ) {
			/**
			 * Action to perform after a fulfillment is fulfilled.
			 *
			 * @param Fulfillment $data The fulfillment object that was fulfilled.
			 *
			 * @since 10.1.0
			 */
			do_action( 'woocommerce_fulfillment_after_fulfill', $data );
		}
	}

	/**
	 * Method to delete a fulfillment from the database.
	 *
	 * @param Fulfillment $data The fulfillment object to delete.
	 * @param array       $args Optional arguments to pass to the delete method.
	 *
	 * @return void
	 *
	 * @throws \Exception If the fulfillment can't be deleted.
	 */
	public function delete( &$data, $args = array() ): void {
		// If the record is already deleted, do nothing.
		if ( $data->get_date_deleted() ) {
			return;
		}

		/**
		 * Filter to modify the fulfillment data before it is updated.
		 *
		 * @since 10.1.0
		 */
		$data = apply_filters( 'woocommerce_fulfillment_before_delete', $data );

		// Soft Delete the fulfillment from the database.
		global $wpdb;

		$data_id       = $data->get_id();
		$deletion_time = current_time( 'mysql' );
		$wpdb->update(
			$wpdb->prefix . 'wc_order_fulfillments',
			array( 'date_deleted' => $deletion_time ),
			array(
				'fulfillment_id' => $data_id,
				'date_deleted'   => null,
			),
			array( '%s' ),
			array( '%d' )
		);

		// Check for errors.
		if ( $wpdb->last_error ) {
			throw new \Exception( esc_html__( 'Failed to delete fulfillment.', 'woocommerce' ) );
		}

		$data->set_date_deleted( $deletion_time );
		$data->apply_changes();
		$data->set_object_read( true );

		if ( ! doing_action( 'woocommerce_fulfillment_after_delete' ) ) {
			/**
			 * Action to perform after a fulfillment is deleted.
			 *
			 * @since 10.1.0
			 */
			do_action( 'woocommerce_fulfillment_after_delete', $data );
		}

		// Set the fulfillment object to a fresh state.
		$data = new Fulfillment();
	}

	/**
	 * Method to read the metadata for a fulfillment.
	 *
	 * @param Fulfillment $data The fulfillment object to read.
	 * @return array
	 *
	 * @throws \Exception If the fulfillment is not saved.
	 */
	public function read_meta( &$data ): array {
		if ( ! $data->get_id() ) {
			throw new \Exception( esc_html__( 'Invalid fulfillment.', 'woocommerce' ) );
		}

		// Read the metadata for the fulfillment.
		global $wpdb;

		$data_id   = $data->get_id();
		$meta_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillment_meta WHERE fulfillment_id = %d",
				$data_id
			),
			OBJECT
		);

		return array_map(
			function ( $meta ) {
				$meta->meta_value = json_decode( $meta->meta_value, true ) ?? $meta->meta_value;
				return $meta;
			},
			$meta_data
		);
	}

	/**
	 * Method to delete the metadata for a fulfillment.
	 *
	 * @param Fulfillment  $data The fulfillment object to delete.
	 * @param WC_Meta_Data $meta Meta object (containing at least ->id).
	 *
	 * @return void
	 *
	 * @throws \Exception If the fulfillment or meta is not saved.
	 */
	public function delete_meta( &$data, $meta ): void {
		// Check if the fulfillment and meta are saved.
		$data_id = $data->get_id();

		// Prevent deletion of metadata from a deleted fulfillment.
		if ( $data->get_date_deleted() ) {
			throw new \Exception( esc_html__( 'Cannot delete meta from a deleted fulfillment.', 'woocommerce' ) );
		}

		$meta_id = $meta->id;
		if ( ! is_numeric( $data_id ) || $data_id <= 0 || ! is_numeric( $meta_id ) || $meta_id <= 0 ) {
			throw new \Exception( esc_html__( 'Invalid fulfillment or meta.', 'woocommerce' ) );
		}

		// Delete the metadata for the fulfillment.
		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . 'wc_order_fulfillment_meta',
			array(
				'fulfillment_id' => $data_id,
				'meta_id'        => $meta_id,
			),
			array(
				'%d',
				'%d',
			)
		);
	}

	/**
	 * Method to add metadata for a fulfillment.
	 *
	 * @param Fulfillment  $data The fulfillment object to save.
	 * @param WC_Meta_Data $meta Meta object (containing at least ->id).
	 * @return int meta ID or WP_Error on failure.
	 *
	 * @throws \Exception If the fulfillment or meta is not saved.
	 */
	public function add_meta( &$data, $meta ): int {
		// Add the metadata for the fulfillment.
		global $wpdb;

		// Prevent adding metadata to a deleted fulfillment.
		if ( $data->get_date_deleted() ) {
			throw new \Exception( esc_html__( 'Cannot add meta to a deleted fulfillment.', 'woocommerce' ) );
		}

		// Data ID can't be something wrong as this function is called after the meta is read.
		// See WC_Data::save_meta_data().
		$data_id = $data->get_id();

		$wpdb->insert(
			$wpdb->prefix . 'wc_order_fulfillment_meta',
			array(
				'fulfillment_id' => $data_id,
				'meta_key'       => $meta->key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => wp_json_encode( $meta->value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			),
			array(
				'%d',
				'%s',
				'%s',
			)
		);

		// Note: There is no error check on WC_Data::save_meta_data(), and it expects us to return an ID in all cases.
		// If there's an error, we should return null to indicate we didn't save it.
		if ( $wpdb->last_error ) {
			throw new \Exception( esc_html__( 'Failed to insert fulfillment meta.', 'woocommerce' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Method to save the metadata for a fulfillment.
	 *
	 * @param Fulfillment  $data The fulfillment object to save.
	 * @param WC_Meta_Data $meta Meta object (containing at least ->id).
	 *
	 * @return int Number of rows updated.
	 *
	 * @throws \Exception If the fulfillment or meta is not saved.
	 */
	public function update_meta( &$data, $meta ): int {
		// Update the metadata for the fulfillment.
		global $wpdb;

		$data_id = $data->get_id();

		// Prevent updating metadata for a deleted fulfillment.
		if ( $data->get_date_deleted() ) {
			throw new \Exception( esc_html__( 'Cannot update meta for a deleted fulfillment.', 'woocommerce' ) );
		}

		$rows_updated = $wpdb->update(
			$wpdb->prefix . 'wc_order_fulfillment_meta',
			array(
				'meta_value' => wp_json_encode( $meta->value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			),
			array(
				'fulfillment_id' => $data_id,
				'meta_id'        => $meta->id,
				'meta_key'       => $meta->key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			),
			array(
				'%s',
			),
			array(
				'%d',
				'%d',
				'%s',
			)
		);

		// Check for errors.
		if ( $wpdb->last_error ) {
			throw new \Exception( esc_html__( 'Failed to update fulfillment meta.', 'woocommerce' ) );
		}

		return $rows_updated;
	}

	/**
	 * Method to read the fulfillment data.
	 *
	 * @param string $entity_type The entity type.
	 * @param string $entity_id The entity ID.
	 * @param bool   $with_deleted Whether to include deleted fulfillments in the results.
	 *
	 * @return Fulfillment[] Fulfillment object.
	 *
	 * @throws \Exception If the fulfillment data can't be read.
	 */
	public function read_fulfillments( string $entity_type, string $entity_id, bool $with_deleted = false ): array {
		// Read the fulfillment data from the database.
		global $wpdb;

		if ( ! $with_deleted ) {
			$fulfillment_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wc_order_fulfillments WHERE entity_type = %s AND entity_id = %s AND date_deleted IS NULL",
					$entity_type,
					$entity_id
				),
				ARRAY_A
			);
		} else {
			$fulfillment_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wc_order_fulfillments WHERE entity_type = %s AND entity_id = %s",
					$entity_type,
					$entity_id
				),
				ARRAY_A
			);
		}

		if ( is_wp_error( $fulfillment_data ) ) {
			throw new \Exception( esc_html__( 'Failed to read fulfillment data.', 'woocommerce' ) );
		}

		// Create Fulfillment objects from the data.
		$fulfillments = array();
		foreach ( $fulfillment_data as $data ) {
			// Note: Don't initialize with ID, it will cause a re-read from the database.
			// Set the ID directly after the object is created.
			$fulfillment = new Fulfillment();
			$fulfillment->set_id( $data['fulfillment_id'] );
			$fulfillment->set_props( $data );
			$fulfillment->apply_changes();
			$fulfillment->set_object_read( true );

			// Read the metadata for the fulfillment.
			$fulfillment->read_meta_data( true );

			$fulfillments[] = $fulfillment;
		}

		return $fulfillments;
	}

	/**
	 * Method to validate the items in a fulfillment.
	 *
	 * @param Fulfillment $data The fulfillment object to validate.
	 *
	 * @return void
	 *
	 * @throws \Exception If the fulfillment data is invalid.
	 */
	private function validate_items( Fulfillment $data ): void {
		$items = $data->get_meta( '_items', true );
		if ( empty( $items ) ) {
			throw new \Exception( esc_html__( 'The fulfillment should contain at least one item.', 'woocommerce' ) );
		}

		if ( ! is_array( $items ) ) {
			throw new \Exception( esc_html__( 'The fulfillment items should be an array.', 'woocommerce' ) );
		}

		foreach ( $data->get_items() as $item ) {
			if ( ! isset( $item['item_id'] )
				// The item ID and qty should be set.
				|| ! isset( $item['qty'] )
				// The item ID should be integers.
				|| ! is_int( $item['item_id'] )
				// Allow the qty to be a float too.
				|| ( ! is_int( $item['qty'] ) && ! is_float( $item['qty'] ) )
				// The item ID and qty should be greater than 0.
				|| $item['item_id'] <= 0
				|| $item['qty'] <= 0
				) {
				throw new \Exception( esc_html__( 'Invalid item.', 'woocommerce' ) );
			}
		}
	}
}
