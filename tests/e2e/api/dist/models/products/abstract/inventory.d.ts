import { Model } from '../../model';
import { BackorderStatus, StockStatus } from '../shared';
/**
 * The base for inventory products.
 */
declare abstract class AbstractProductInventory extends Model {
    /**
     * Indicates that only one of a product may be held in the order at a time.
     *
     * @type {boolean}
     */
    readonly onePerOrder: boolean;
    /**
     * Indicates that a product should use the inventory system.
     *
     * @type {boolean}
     */
    readonly trackInventory: boolean;
    /**
     * The number of inventory units remaining for this product.
     *
     * @type {number}
     */
    readonly remainingStock: number;
    /**
     * The product's stock status.
     *
     * @type {StockStatus}
     */
    readonly stockStatus: StockStatus;
    /**
     * The status of backordering for a product.
     *
     * @type {BackorderStatus}
     */
    readonly backorderStatus: BackorderStatus;
    /**
     * Indicates whether or not a product can be backordered.
     *
     * @type {boolean}
     */
    readonly canBackorder: boolean;
    /**
     * Indicates whether or not a product is on backorder.
     *
     * @type {boolean}
     */
    readonly isOnBackorder: boolean;
}
export interface IProductInventory extends AbstractProductInventory {
}
export {};
//# sourceMappingURL=inventory.d.ts.map