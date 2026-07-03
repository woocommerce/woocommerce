<?php
/**
 * ProductInventoryService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiLocationInventory;

defined( 'ABSPATH' ) || exit;

/**
 * Atomic stock ledger over wc_product_inventory. The only writer of that table.
 *
 * @internal
 */
class ProductInventoryService {

	/**
	 * Get the wc_product_inventory table name.
	 */
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'wc_product_inventory';
	}

	/**
	 * Get stock for a (product, variation, location) triple.
	 *
	 * @param int $product_id   Product id (parent id for variations).
	 * @param int $variation_id Variation id, or 0.
	 * @param int $location_id  Location id.
	 * @return float The stock quantity.
	 */
	public function get_stock( int $product_id, int $variation_id, int $location_id ): float {
		global $wpdb;

		$table_name = $this->get_table_name();
		$quantity   = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT quantity FROM %i WHERE product_id = %d AND variation_id = %d AND location_id = %d',
				$table_name,
				$product_id,
				$variation_id,
				$location_id
			)
		);

		return null === $quantity ? 0.0 : (float) wc_stock_amount( $quantity );
	}

	/**
	 * Set (overwrite) stock for a triple. Upserts.
	 *
	 * @param int   $product_id   Product id.
	 * @param int   $variation_id Variation id, or 0.
	 * @param int   $location_id  Location id.
	 * @param float $quantity     New quantity.
	 * @return float The set quantity.
	 * @throws \Exception When the upsert fails.
	 */
	public function set_stock( int $product_id, int $variation_id, int $location_id, float $quantity ): float {
		global $wpdb;

		$quantity   = (float) wc_stock_amount( $quantity );
		$table_name = $this->get_table_name();

		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i ( product_id, variation_id, location_id, quantity )
				VALUES ( %d, %d, %d, %f )
				ON DUPLICATE KEY UPDATE quantity = VALUES( quantity )',
				$table_name,
				$product_id,
				$variation_id,
				$location_id,
				$quantity
			)
		);

		if ( false === $result ) {
			throw new \Exception( esc_html__( 'Failed to set product inventory.', 'woocommerce' ) );
		}

		return $quantity;
	}

	/**
	 * Increase stock for a triple. Upserts.
	 *
	 * @param int   $product_id   Product id.
	 * @param int   $variation_id Variation id, or 0.
	 * @param int   $location_id  Location id.
	 * @param float $quantity     Amount to add.
	 * @return float The new quantity.
	 * @throws \Exception When the upsert fails.
	 */
	public function increase_stock( int $product_id, int $variation_id, int $location_id, float $quantity ): float {
		global $wpdb;

		$quantity = (float) wc_stock_amount( $quantity );
		if ( $quantity <= 0.0 ) {
			return $this->get_stock( $product_id, $variation_id, $location_id );
		}

		$table_name = $this->get_table_name();

		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i ( product_id, variation_id, location_id, quantity )
				VALUES ( %d, %d, %d, %f )
				ON DUPLICATE KEY UPDATE quantity = quantity + VALUES( quantity )',
				$table_name,
				$product_id,
				$variation_id,
				$location_id,
				$quantity
			)
		);

		if ( false === $result ) {
			throw new \Exception( esc_html__( 'Failed to increase product inventory.', 'woocommerce' ) );
		}

		return $this->get_stock( $product_id, $variation_id, $location_id );
	}

	/**
	 * Decrease stock atomically for a triple.
	 *
	 * The single UPDATE with `quantity >= n` in the WHERE clause guarantees the quantity
	 * can never go negative under concurrent writes: the row is only touched when enough
	 * stock is present, and success is detected via the affected-row count.
	 *
	 * @param int   $product_id   Product id.
	 * @param int   $variation_id Variation id, or 0.
	 * @param int   $location_id  Location id.
	 * @param float $quantity     Amount to remove.
	 * @return float|null The new quantity, or null when the row is missing or stock is insufficient.
	 * @throws \Exception When the update fails.
	 */
	public function decrease_stock( int $product_id, int $variation_id, int $location_id, float $quantity ): ?float {
		global $wpdb;

		$quantity = (float) wc_stock_amount( $quantity );
		if ( $quantity <= 0.0 ) {
			return $this->get_stock( $product_id, $variation_id, $location_id );
		}

		$table_name = $this->get_table_name();
		$updated    = $wpdb->query(
			$wpdb->prepare(
				'UPDATE %i
				SET quantity = quantity - %f
				WHERE product_id = %d AND variation_id = %d AND location_id = %d AND quantity >= %f',
				$table_name,
				$quantity,
				$product_id,
				$variation_id,
				$location_id,
				$quantity
			)
		);

		if ( false === $updated ) {
			throw new \Exception( esc_html__( 'Failed to update product inventory.', 'woocommerce' ) );
		}

		if ( 1 !== $updated ) {
			return null;
		}

		return $this->get_stock( $product_id, $variation_id, $location_id );
	}
}
