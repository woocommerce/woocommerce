/**
 * Internal dependencies
 */
import { Model } from '../../model';
import { BackorderStatus, StockStatus } from '../shared';

/**
 * The base for inventory products.
 */
abstract class AbstractProductInventory extends Model {
	/**
	 * Indicates that only one of a product may be held in the order at a time.
	 *
	 * @type {boolean}
	 */
	public readonly onePerOrder: boolean = false;

	/**
	 * Indicates that a product should use the inventory system.
	 *
	 * @type {boolean}
	 */
	public readonly trackInventory: boolean = false;

	/**
	 * The number of inventory units remaining for this product.
	 *
	 * @type {number}
	 */
	public readonly remainingStock: number = -1;

	/**
	 * The product's stock status.
	 *
	 * @type {StockStatus}
	 */
	public readonly stockStatus: StockStatus = '';

	/**
	 * The status of backordering for a product.
	 *
	 * @type {BackorderStatus}
	 */
	public readonly backorderStatus: BackorderStatus = BackorderStatus.Allowed;

	/**
	 * Indicates whether or not a product can be backordered.
	 *
	 * @type {boolean}
	 */
	public readonly canBackorder: boolean = false;

	/**
	 * Indicates whether or not a product is on backorder.
	 *
	 * @type {boolean}
	 */
	public readonly isOnBackorder: boolean = false;

	/**
	 * Indicates the threshold for when the low stock notification will be sent to the merchant.
	 *
	 * @type {number}
	 */
	public readonly lowStockThreshold: number = -1;
}

export type IProductInventory = AbstractProductInventory;
