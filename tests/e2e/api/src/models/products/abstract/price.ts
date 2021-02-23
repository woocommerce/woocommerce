import { Model } from '../../model';

/**
 * The base for price properties.
 */
abstract class AbstractProductPrice extends Model {
	/**
	 * The current price of the product.
	 *
	 * @type {string}
	 */
	public readonly price: string = '';

	/**
	 * The rendered HTML for the current price of the product.
	 *
	 * @type {string}
	 */
	public readonly priceHtml: string = '';

	/**
	 * The regular price of the product when not discounted.
	 *
	 * @type {string}
	 */
	public readonly regularPrice: string = '';

	/**
	 * Indicates whether or not the product is currently on sale.
	 *
	 * @type {boolean}
	 */
	public readonly onSale: boolean = false;

	/**
	 * The price of the product when on sale.
	 *
	 * @type {string}
	 */
	public readonly salePrice: string = '';

	/**
	 * The GMT datetime when the product should start to be on sale.
	 *
	 * @type {Date|null}
	 */
	public readonly saleStart: Date | null = null;

	/**
	 * The GMT datetime when the product should no longer be on sale.
	 *
	 * @type {Date|null}
	 */
	public readonly saleEnd: Date | null = null;
}

export interface IProductPrice extends AbstractProductPrice {}
