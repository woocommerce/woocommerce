import { Model } from '../../model';
import { MetaData, PostStatus, ObjectLinks } from '../../shared-types';
import { ProductImage } from '../shared';
/**
 * Base product data.
 */
export declare abstract class AbstractProductData extends Model {
    /**
     * The permalink of the product.
     *
     * @type {string}
     */
    readonly permalink: string;
    /**
     * The Id of the product.
     *
     * @type {number}
     */
    readonly id: number;
    /**
     * The parent Id of the product.
     *
     * @type {number}
     */
    readonly parentId: number;
    /**
     * The menu order assigned to the product.
     *
     * @type {number}
     */
    readonly menuOrder: number;
    /**
     * The GMT datetime when the product was created.
     *
     * @type {Date}
     */
    readonly created: Date;
    /**
     * The GMT datetime when the product was last modified.
     *
     * @type {Date}
     */
    readonly modified: Date;
    /**
     * The product's current post status.
     *
     * @type {PostStatus}
     */
    readonly postStatus: PostStatus;
    /**
     * The product's full description.
     *
     * @type {string}
     */
    readonly description: string;
    /**
     * The product's SKU.
     *
     * @type {string}
     */
    readonly sku: string;
    /**
     * Indicates whether or not the product is currently able to be purchased.
     *
     * @type {boolean}
     */
    readonly isPurchasable: boolean;
    /**
     * The images for the product.
     *
     * @type {ReadonlyArray.<ProductImage>}
     */
    readonly images: readonly ProductImage[];
    /**
     * The extra metadata for the product.
     *
     * @type {ReadonlyArray.<MetaData>}
     */
    readonly metaData: readonly MetaData[];
    /**
     * The product data links.
     *
     * @type {ReadonlyArray.<ObjectLinks>}
     */
    readonly links: ObjectLinks;
}
//# sourceMappingURL=data.d.ts.map