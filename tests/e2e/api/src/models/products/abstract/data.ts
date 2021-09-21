import { Model } from '../../model';
import { MetaData, PostStatus, ObjectLinks } from '../../shared-types';
import { ProductImage } from '../shared';

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
	 * The images for the product.
	 *
	 * @type {ReadonlyArray.<ProductImage>}
	 */
	public readonly images: readonly ProductImage[] = [];

	/**
	 * The extra metadata for the product.
	 *
	 * @type {ReadonlyArray.<MetaData>}
	 */
	public readonly metaData: readonly MetaData[] = [];

	/**
	 * The product data links.
	 *
	 * @type {ReadonlyArray.<ObjectLinks>}
	 */
	public readonly links: ObjectLinks = {
		collection: [ { href: '' } ],
		self: [ { href: '' } ],
	};
}
