import { Model } from '../../model';

/**
 * The base for external products.
 */
abstract class AbstractProductExternal extends Model {
	/**
	 * The product's button text.
	 *
	 * @type {string}
	 */
	public readonly buttonText: string = ''

	/**
	 * The product's external URL.
	 *
	 * @type {string}
	 */
	public readonly externalUrl: string = ''
}

export interface IProductExternal extends AbstractProductExternal {}
