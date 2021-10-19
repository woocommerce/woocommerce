import { Model } from '../../model';
import { ProductDownload } from '../shared';
/**
 * The base for Delivery products.
 */
declare abstract class AbstractProductDelivery extends Model {
    /**
     * Indicates that the product is delivered virtually.
     *
     * @type {boolean}
     */
    readonly isVirtual: boolean;
    /**
     * Indicates whether or not the product is downloadable.
     *
     * @type {boolean}
     */
    readonly isDownloadable: boolean;
    /**
     * The downloads available for the product.
     *
     * @type {ReadonlyArray.<ProductDownload>}
     */
    readonly downloads: readonly ProductDownload[];
    /**
     * The maximum number of times a customer may download the product's contents.
     *
     * @type {number}
     */
    readonly downloadLimit: number;
    /**
     * The number of days after purchase that a customer may still download the product's contents.
     *
     * @type {number}
     */
    readonly daysToDownload: number;
    /**
     * The text shown to the customer on completing the purchase of this product.
     *
     * @type {string}
     */
    readonly purchaseNote: string;
}
export interface IProductDelivery extends AbstractProductDelivery {
}
export {};
//# sourceMappingURL=delivery.d.ts.map