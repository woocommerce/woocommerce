import { Model } from '../../model';
/**
 * The base for cross sells.
 */
declare abstract class AbstractProductGrouped extends Model {
    /**
     * An array of grouped product ids.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly groupedProducts: Array<number>;
}
export interface IProductGrouped extends AbstractProductGrouped {
}
export {};
//# sourceMappingURL=grouped.d.ts.map