import { Model } from '../../model';
/**
 * The base for cross sells.
 */
declare abstract class AbstractProductCrossSells extends Model {
    /**
     * An array of cross sell product ids.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly crossSellIds: Array<number>;
}
export interface IProductCrossSells extends AbstractProductCrossSells {
}
export {};
//# sourceMappingURL=cross-sell.d.ts.map