import { Model } from '../../model';
/**
 * The base class for product shipping.
 */
declare abstract class AbstractProductShipping extends Model {
    /**
     * The weight of the product in the store's current units.
     *
     * @type {string}
     */
    readonly weight: string;
    /**
     * The length of the product in the store's current units.
     *
     * @type {string}
     */
    readonly length: string;
    /**
     * The width of the product in the store's current units.
     *
     * @type {string}
     */
    readonly width: string;
    /**
     * The height of the product in the store's current units.
     *
     * @type {string}
     */
    readonly height: string;
    /**
     * Indicates that the product must be shipped.
     *
     * @type {boolean}
     */
    readonly requiresShipping: boolean;
    /**
     * Indicates that the product's shipping is taxable.
     *
     * @type {boolean}
     */
    readonly isShippingTaxable: boolean;
    /**
     * The shipping class for the product.
     *
     * @type {string}
     */
    readonly shippingClass: string;
    /**
     * The shipping class ID for the product.
     *
     * @type {number}
     */
    readonly shippingClassId: number;
}
export interface IProductShipping extends AbstractProductShipping {
}
export {};
//# sourceMappingURL=shipping.d.ts.map