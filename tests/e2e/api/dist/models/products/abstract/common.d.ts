import { AbstractProductData } from './data';
import { ModelID } from '../../model';
import { CatalogVisibility, ProductTerm, ProductAttribute } from '../shared';
import { ObjectLinks } from '../../shared-types';
/**
 * The common parameters that all products can use in search.
 */
export declare type ProductSearchParams = {
    search: string;
};
/**
 * The base product URL.
 *
 * @return {string} RESTful Url.
 */
export declare const baseProductURL: () => string;
/**
 * A common product URL builder.
 *
 * @param {ModelID} id the id of the product.
 * @return {string} RESTful Url.
 */
export declare const buildProductURL: (id: ModelID) => string;
/**
 * A common delete product URL builder.
 *
 * @param {ModelID} id the id of the product.
 * @return {string} RESTful Url.
 */
export declare const deleteProductURL: (id: ModelID) => string;
/**
 * The base for all product types.
 */
export declare abstract class AbstractProduct extends AbstractProductData {
    /**
     * The name of the product.
     *
     * @type {string}
     */
    readonly name: string;
    /**
     * The slug of the product.
     *
     * @type {string}
     */
    readonly slug: string;
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
     * The product's short description.
     *
     * @type {string}
     */
    readonly shortDescription: string;
    /**
     * An array of the categories this product is in.
     *
     * @type {ReadonlyArray.<ProductTerm>}
     */
    readonly categories: readonly ProductTerm[];
    /**
     * An array of the tags this product has.
     *
     * @type {ReadonlyArray.<ProductTerm>}
     */
    readonly tags: readonly ProductTerm[];
    /**
     * Indicates whether or not the product should be featured.
     *
     * @type {boolean}
     */
    readonly isFeatured: boolean;
    /**
     * Indicates whether or not the product should be visible in the catalog.
     *
     * @type {CatalogVisibility}
     */
    readonly catalogVisibility: CatalogVisibility;
    /**
     * The count of sales of the product
     *
     * @type {number}
     */
    readonly totalSales: number;
    /**
     * Indicates whether or not a product allows reviews.
     *
     * @type {boolean}
     */
    readonly allowReviews: boolean;
    /**
     * The average rating for the product.
     *
     * @type {number}
     */
    readonly averageRating: number;
    /**
     * The number of ratings for the product.
     *
     * @type {number}
     */
    readonly numRatings: number;
    /**
     * An array of IDs of related products.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly relatedIds: Array<number>;
    /**
     * The attributes for the product.
     *
     * @type {ReadonlyArray.<ProductAttribute>}
     */
    readonly attributes: readonly ProductAttribute[];
    /**
     * The product's links.
     *
     * @type {ReadonlyArray.<ObjectLinks>}
     */
    readonly links: ObjectLinks;
}
export interface IProductCommon extends AbstractProduct {
}
//# sourceMappingURL=common.d.ts.map