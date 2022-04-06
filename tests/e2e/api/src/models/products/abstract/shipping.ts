import { Model } from '../../model';

/**
 * The base class for product shipping.
 */
abstract class AbstractProductShipping extends Model {
	/**
	 * The weight of the product in the store's current units.
	 *
	 * @type {string}
	 */
	public readonly weight: string = '';

	/**
	 * The length of the product in the store's current units.
	 *
	 * @type {string}
	 */
	public readonly length: string = '';

	/**
	 * The width of the product in the store's current units.
	 *
	 * @type {string}
	 */
	public readonly width: string = '';

	/**
	 * The height of the product in the store's current units.
	 *
	 * @type {string}
	 */
	public readonly height: string = '';

	/**
	 * Indicates that the product must be shipped.
	 *
	 * @type {boolean}
	 */
	public readonly requiresShipping: boolean = false;

	/**
	 * Indicates that the product's shipping is taxable.
	 *
	 * @type {boolean}
	 */
	public readonly isShippingTaxable: boolean = false;

	/**
	 * The shipping class for the product.
	 *
	 * @type {string}
	 */
	public readonly shippingClass: string = '';

	/**
	 * The shipping class ID for the product.
	 *
	 * @type {number}
	 */
	public readonly shippingClassId: number = 0;
}

export interface IProductShipping extends AbstractProductShipping {}
