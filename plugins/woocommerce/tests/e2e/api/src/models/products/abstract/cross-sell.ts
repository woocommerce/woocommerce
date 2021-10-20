import { Model } from '../../model';

/**
 * The base for cross sells.
 */
abstract class AbstractProductCrossSells extends Model {
	/**
	 * An array of cross sell product ids.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly crossSellIds: Array<number> = [];
}

export interface IProductCrossSells extends AbstractProductCrossSells {}
