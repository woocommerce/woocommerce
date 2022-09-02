import { Model } from '../../model';
import { Taxability } from '../shared';

/**
 * The base for products with sales tax.
 */
abstract class AbstractProductSalesTax extends Model {
	/**
	 * The taxability of the product.
	 *
	 * @type {Taxability}
	 */
	public readonly taxStatus: Taxability = Taxability.ProductAndShipping;

	/**
	 * The tax class of the product
	 *
	 * @type {string}
	 */
	public readonly taxClass: string = '';
}

export interface IProductSalesTax extends AbstractProductSalesTax {}
