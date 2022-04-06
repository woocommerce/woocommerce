import { Model } from '../../model';
import { ProductDownload } from '../shared';

/**
 * The base for Delivery products.
 */
abstract class AbstractProductDelivery extends Model {
	/**
	 * Indicates that the product is delivered virtually.
	 *
	 * @type {boolean}
	 */
	public readonly isVirtual: boolean = false;

	/**
	 * Indicates whether or not the product is downloadable.
	 *
	 * @type {boolean}
	 */
	public readonly isDownloadable: boolean = false;

	/**
	 * The downloads available for the product.
	 *
	 * @type {ReadonlyArray.<ProductDownload>}
	 */
	public readonly downloads: readonly ProductDownload[] = [];

	/**
	 * The maximum number of times a customer may download the product's contents.
	 *
	 * @type {number}
	 */
	public readonly downloadLimit: number = -1;

	/**
	 * The number of days after purchase that a customer may still download the product's contents.
	 *
	 * @type {number}
	 */
	public readonly daysToDownload: number = -1;

	/**
	 * The text shown to the customer on completing the purchase of this product.
	 *
	 * @type {string}
	 */
	public readonly purchaseNote: string = '';
}

export interface IProductDelivery extends AbstractProductDelivery {}
