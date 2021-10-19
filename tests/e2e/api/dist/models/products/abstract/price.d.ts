import { Model } from '../../model';
/**
 * The base for price properties.
 */
declare abstract class AbstractProductPrice extends Model {
    /**
     * The current price of the product.
     *
     * @type {string}
     */
    readonly price: string;
    /**
     * The rendered HTML for the current price of the product.
     *
     * @type {string}
     */
    readonly priceHtml: string;
    /**
     * The regular price of the product when not discounted.
     *
     * @type {string}
     */
    readonly regularPrice: string;
    /**
     * Indicates whether or not the product is currently on sale.
     *
     * @type {boolean}
     */
    readonly onSale: boolean;
    /**
     * The price of the product when on sale.
     *
     * @type {string}
     */
    readonly salePrice: string;
    /**
     * The GMT datetime when the product should start to be on sale.
     *
     * @type {Date|null}
     */
    readonly saleStart: Date | null;
    /**
     * The GMT datetime when the product should no longer be on sale.
     *
     * @type {Date|null}
     */
    readonly saleEnd: Date | null;
}
export interface IProductPrice extends AbstractProductPrice {
}
export {};
//# sourceMappingURL=price.d.ts.map