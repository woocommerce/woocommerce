import { Model } from '../../model';
/**
 * The base for upsells.
 */
declare abstract class AbstractProductUpSells extends Model {
    /**
     * An array of upsell product ids.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly upSellIds: Array<number>;
}
export interface IProductUpSells extends AbstractProductUpSells {
}
export {};
//# sourceMappingURL=upsell.d.ts.map