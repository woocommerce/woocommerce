import { Model } from '../model';
import { MetaData, PostStatus } from '../shared-types';
import {
	BackorderStatus,
	CatalogVisibility,
	ProductAttribute,
	ProductDownload,
	ProductImage,
	ProductTerm, StockStatus,
	Taxability,
} from './shared-types';

/**
 * The common parameters that all products can use in search.
 */
export type ProductSearchParams = { search: string };

/**
 * The common parameters that all products can update.
 */
export type ProductUpdateParams = 'name' | 'slug' | 'created' | 'postStatus' | 'shortDescription'
	| 'description' | 'sku' | 'categories' | 'tags' | 'isFeatured'
	| 'isVirtual' | 'attributes' | 'images' | 'catalogVisibility'
	| 'regularPrice' | 'onePerOrder' | 'taxStatus' | 'taxClass'
	| 'salePrice' | 'saleStart' | 'saleEnd' | 'isDownloadable'
	| 'downloadLimit' | 'daysToDownload' | 'weight' | 'length'
	| 'width' | 'height' | 'trackInventory' | 'remainingStock'
	| 'stockStatus' | 'backorderStatus' | 'allowReviews'
	| 'metaData';

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
	 * The slug of the product.
	 *
	 * @type {string}
	 */
	public readonly slug: string = '';

	/**
	 * The permalink of the product.
	 *
	 * @type {string}
	 */
	public readonly permalink: string = '';

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
	 * The product's short description.
	 *
	 * @type {string}
	 */
	public readonly shortDescription: string = '';

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
	 * An array of the categories this product is in.
	 *
	 * @type {ReadonlyArray.<ProductTerm>}
	 */
	public readonly categories: readonly ProductTerm[] = [];

	/**
	 * An array of the tags this product has.
	 *
	 * @type {ReadonlyArray.<ProductTerm>}
	 */
	public readonly tags: readonly ProductTerm[] = [];

	/**
	 * Indicates whether or not the product is currently able to be purchased.
	 *
	 * @type {boolean}
	 */
	public readonly isPurchasable: boolean = true;

	/**
	 * Indicates whether or not the product should be featured.
	 *
	 * @type {boolean}
	 */
	public readonly isFeatured: boolean = false;

	/**
	 * Indicates that the product is delivered virtually.
	 *
	 * @type {boolean}
	 */
	public readonly isVirtual: boolean = false;

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

	/**
	 * Indicates whether or not the product should be visible in the catalog.
	 *
	 * @type {CatalogVisibility}
	 */
	public readonly catalogVisibility: CatalogVisibility = CatalogVisibility.Everywhere;

	/**
	 * The current price of the product.
	 *
	 * @type {string}
	 */
	public readonly price: string = '';

	/**
	 * The regular price of the product when not discounted.
	 *
	 * @type {string}
	 */
	public readonly regularPrice: string = '';

	/**
	 * Indicates that only one of a product may be held in the order at a time.
	 *
	 * @type {boolean}
	 */
	public readonly onePerOrder: boolean = false;

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
	 * Indicates that a product should use the inventory system.
	 *
	 * @type {boolean}
	 */
	public readonly trackInventory: boolean = false;

	/**
	 * The number of inventory units remaining for this product.
	 *
	 * @type {number}
	 */
	public readonly remainingStock: number = -1;

	/**
	 * The product's stock status.
	 *
	 * @type {StockStatus}
	 */
	public readonly stockStatus: StockStatus = ''

	/**
	 * The status of backordering for a product.
	 *
	 * @type {BackorderStatus}
	 */
	public readonly backorderStatus: BackorderStatus = BackorderStatus.Allowed;

	/**
	 * Indicates whether or not a product can be backordered.
	 *
	 * @type {boolean}
	 */
	public readonly canBackorder: boolean = false;

	/**
	 * Indicates whether or not a product is on backorder.
	 *
	 * @type {boolean}
	 */
	public readonly isOnBackorder: boolean = false;

	/**
	 * Indicates whether or not a product allows reviews.
	 *
	 * @type {boolean}
	 */
	public readonly allowReviews: boolean = false;

	/**
	 * The average rating for the product.
	 *
	 * @type {number}
	 */
	public readonly averageRating: number = -1;

	/**
	 * The number of ratings for the product.
	 *
	 * @type {number}
	 */
	public readonly numRatings: number = -1;

	/**
	 * The extra metadata for the product.
	 *
	 * @type {ReadonlyArray.<MetaData>}
	 */
	public readonly metaData: readonly MetaData[] = [];
}
