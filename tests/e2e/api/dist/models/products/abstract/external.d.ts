import { Model } from '../../model';
/**
 * The base for external products.
 */
declare abstract class AbstractProductExternal extends Model {
    /**
     * The product's button text.
     *
     * @type {string}
     */
    readonly buttonText: string;
    /**
     * The product's external URL.
     *
     * @type {string}
     */
    readonly externalUrl: string;
}
export interface IProductExternal extends AbstractProductExternal {
}
export {};
//# sourceMappingURL=external.d.ts.map