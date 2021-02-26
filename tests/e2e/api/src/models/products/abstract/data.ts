import { Model } from '../../model';
import { MetaData, PostStatus } from '../../shared-types';
import {
	ProductAttribute,
	ProductImage,
} from '../shared';

/**
 * Base product data.
 */
export abstract class AbstractProductData extends Model {
	/**
	 * The permalink of the product.
	 *
	 * @type {string}
	 */
	public readonly permalink: string = '';

	/**
	 * The Id of the product.
	 *
	 * @type {number}
	 */
	public readonly id: number = 0;

	/**
	 * The parent Id of the product.
	 *
	 * @type {number}
	 */
	public readonly parentId: number = 0;

	/**
	 * The menu order assigned to the product.
	 *
	 * @type {number}
	 */
	public readonly menuOrder: number = 0;

	/**
	 * The GMT datetime when the product was created.
	 *
	 * @type {Date}
	 */
	public readonly created: Date = new Date();

	/**
	 * The GMT datetime when the product was last modified.
	 *
	 * @type {Date}
	 */
	public readonly modified: Date = new Date();

	/**
	 * The product's current post status.
	 *
	 * @type {PostStatus}
	 */
	public readonly postStatus: PostStatus = '';

	/**
	 * The product's full description.
	 *
	 * @type {string}
	 */
	public readonly description: string = '';

	/**
	 * The product's SKU.
	 *
	 * @type {string}
	 */
	public readonly sku: string = '';

	/**
	 * Indicates whether or not the product is currently able to be purchased.
	 *
	 * @type {boolean}
	 */
	public readonly isPurchasable: boolean = true;

	/**
	 * The attributes for the product.
	 *
	 * @type {ReadonlyArray.<ProductAttribute>}
	 */
	public readonly attributes: readonly ProductAttribute[] = [];

	/**
	 * The images for the product.
	 *
	 * @type {ReadonlyArray.<ProductImage>}
	 */
	public readonly images: readonly ProductImage[] = [];

	// @todo: remove price properties once https://github.com/woocommerce/woocommerce/issues/28885 is merged.
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

	/**
	 * The extra metadata for the product.
	 *
	 * @type {ReadonlyArray.<MetaData>}
	 */
	public readonly metaData: readonly MetaData[] = [];
}
