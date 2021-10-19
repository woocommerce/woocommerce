import { Model } from '../model';
import { HTTPClient } from '../../http';
import { couponRESTRepository } from '../../repositories';
import { ModelRepositoryParams, CreatesModels, ListsModels, ReadsModels, UpdatesModels, DeletesModels } from '../../framework';
import { CouponUpdateParams } from './shared';
import { ObjectLinks } from '../shared-types';
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export declare type CouponRepositoryParams = ModelRepositoryParams<Coupon, never, never, CouponUpdateParams>;
/**
 * An interface for creating coupons using the repository.
 *
 * @typedef CreatesCoupons
 * @alias CreatesModels.<Coupon>
 */
export declare type CreatesCoupons = CreatesModels<CouponRepositoryParams>;
/**
 * An interface for reading coupons using the repository.
 *
 * @typedef ReadsCoupons
 * @alias ReadsModels.<Coupon>
 */
export declare type ReadsCoupons = ReadsModels<CouponRepositoryParams>;
/**
 * An interface for updating coupons using the repository.
 *
 * @typedef UpdatesCoupons
 * @alias UpdatesModels.<Coupon>
 */
export declare type UpdatesCoupons = UpdatesModels<CouponRepositoryParams>;
/**
 * An interface for listing coupons using the repository.
 *
 * @typedef ListsCoupons
 * @alias ListsModels.<Coupon>
 */
export declare type ListsCoupons = ListsModels<CouponRepositoryParams>;
/**
 * An interface for deleting coupons using the repository.
 *
 * @typedef DeletesCoupons
 * @alias DeletesModels.<Coupons>
 */
export declare type DeletesCoupons = DeletesModels<CouponRepositoryParams>;
/**
 * The type of discount that is available for the coupon.
 */
declare type DiscountType = 'percent' | 'fixed_cart' | 'fixed_product' | string;
/**
 * A coupon object.
 */
export declare class Coupon extends Model {
    /**
     * The coupon code.
     *
     * @type {string}
     */
    readonly code: string;
    /**
     * The amount of the discount, must always be numeric.
     *
     * @type {string}
     */
    readonly amount: string;
    /**
     * The date the coupon was created.
     *
     * @type {Date}
     */
    readonly dateCreated: Date;
    /**
     * The date the coupon was modified.
     *
     * @type {Date}
     */
    readonly dateModified: Date;
    /**
     * The discount type for the coupon.
     *
     * @type {string}
     */
    readonly discountType: string | DiscountType;
    /**
     * The description of the coupon.
     *
     * @type {string}
     */
    readonly description: string;
    /**
     * The date the coupon expires.
     *
     * @type {Date}
     */
    readonly dateExpires: Date;
    /**
     * The number of times the coupon has already been used.
     *
     * @type {number}
     */
    readonly usageCount: Number;
    /**
     * Flags if the coupon can only be used on its own and not combined with other coupons.
     *
     * @type {boolean}
     */
    readonly individualUse: boolean;
    /**
     * List of Product IDs that the coupon can be applied to.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly productIds: Array<number>;
    /**
     * List of Product IDs that the coupon cannot be applied to.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly excludedProductIds: Array<number>;
    /**
     * How many times the coupon can be used.
     *
     * @type {number}
     */
    readonly usageLimit: Number;
    /**
     * How many times the coupon can be used per customer.
     *
     * @type {number}
     */
    readonly usageLimitPerUser: Number;
    /**
     * Max number of items in the cart the coupon can be applied to.
     *
     * @type {number}
     */
    readonly limitUsageToXItems: Number;
    /**
     * Flags if the free shipping option requires a coupon. This coupon will enable free shipping.
     *
     * @type {boolean}
     */
    readonly freeShipping: boolean;
    /**
     * List of Category IDs the coupon applies to.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly productCategories: Array<number>;
    /**
     * List of Category IDs the coupon does not apply to.
     *
     * @type {ReadonlyArray.<number>}
     */
    readonly excludedProductCategories: Array<number>;
    /**
     * Flags if the coupon applies to items on sale.
     *
     * @type {boolean}
     */
    readonly excludeSaleItems: boolean;
    /**
     * The minimum order amount that needs to be in the cart before the coupon applies.
     *
     * @type {string}
     */
    readonly minimumAmount: string;
    /**
     * The maximum order amount allowed when using the coupon.
     *
     * @type {string}
     */
    readonly maximumAmount: string;
    /**
     * List of email addresses that can use this coupon.
     *
     * @type {ReadonlyArray.<string>}
     */
    readonly emailRestrictions: Array<string>;
    /**
     * List of user IDs (or guest emails) that have used the coupon.
     *
     * @type {ReadonlyArray.<string>}
     */
    readonly usedBy: Array<string>;
    /**
     * The coupon's links.
     *
     * @type {ReadonlyArray.<ObjectLinks>}
     */
    readonly links: ObjectLinks;
    /**
     * Creates a new coupon instance with the given properties
     *
     * @param {Object} properties The properties to set in the object.
     */
    constructor(properties?: Partial<Coupon>);
    /**
     * Returns the repository for interacting with this type of model.
     *
     * @param {HTTPClient} httpClient The client for communicating via HTTP.
     */
    static restRepository(httpClient: HTTPClient): ReturnType<typeof couponRESTRepository>;
}
export {};
//# sourceMappingURL=coupon.d.ts.map