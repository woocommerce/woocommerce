import { Model } from '../../model';

/**
 * The base for upsells.
 */
abstract class AbstractProductUpSells extends Model {
	/**
	 * An array of upsell product ids.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly upSellIds: Array<number> = [];
}

export interface IProductUpSells extends AbstractProductUpSells {}
