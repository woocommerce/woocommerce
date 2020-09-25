import { Model } from '../model';

/**
 * The base class for all product types.
 */
export abstract class AbstractProduct extends Model {
	/**
	 * The name of the product.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';

	/**
	 * The regular price of the product when not discounted.
	 *
	 * @type {string}
	 */
	public readonly regularPrice: string = '';
}
