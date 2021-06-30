import { Model } from '../../model';

/**
 * The base for cross sells.
 */
abstract class AbstractProductGrouped extends Model {
	/**
	 * An array of grouped product ids.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly groupedProducts: Array<number> = [];
}

export interface IProductGrouped extends AbstractProductGrouped {}
