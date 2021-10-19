import { Model } from '../../model';
import { Taxability } from '../shared';
/**
 * The base for products with sales tax.
 */
declare abstract class AbstractProductSalesTax extends Model {
    /**
     * The taxability of the product.
     *
     * @type {Taxability}
     */
    readonly taxStatus: Taxability;
    /**
     * The tax class of the product
     *
     * @type {string}
     */
    readonly taxClass: string;
}
export interface IProductSalesTax extends AbstractProductSalesTax {
}
export {};
//# sourceMappingURL=sales-tax.d.ts.map