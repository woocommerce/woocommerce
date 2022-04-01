import { Model } from '../model';
import { HTTPClient } from '../../http';
import { couponRESTRepository } from '../../repositories';
import {
	ModelRepositoryParams,
	CreatesModels,
	ListsModels,
	ReadsModels,
	UpdatesModels,
	DeletesModels,
} from '../../framework';
import {
	CouponUpdateParams,
} from './shared';
import { ObjectLinks } from '../shared-types';

/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type CouponRepositoryParams = ModelRepositoryParams< Coupon, never, never, CouponUpdateParams >;

/**
 * An interface for creating coupons using the repository.
 *
 * @typedef CreatesCoupons
 * @alias CreatesModels.<Coupon>
 */
export type CreatesCoupons = CreatesModels< CouponRepositoryParams >;

/**
 * An interface for reading coupons using the repository.
 *
 * @typedef ReadsCoupons
 * @alias ReadsModels.<Coupon>
 */
export type ReadsCoupons = ReadsModels< CouponRepositoryParams >;

/**
 * An interface for updating coupons using the repository.
 *
 * @typedef UpdatesCoupons
 * @alias UpdatesModels.<Coupon>
 */
export type UpdatesCoupons = UpdatesModels< CouponRepositoryParams >;

/**
 * An interface for listing coupons using the repository.
 *
 * @typedef ListsCoupons
 * @alias ListsModels.<Coupon>
 */
export type ListsCoupons = ListsModels< CouponRepositoryParams >;

/**
 * An interface for deleting coupons using the repository.
 *
 * @typedef DeletesCoupons
 * @alias DeletesModels.<Coupons>
 */
export type DeletesCoupons = DeletesModels< CouponRepositoryParams >;

/**
 * The type of discount that is available for the coupon.
 */
type DiscountType = 'percent' | 'fixed_cart' | 'fixed_product' | string;

/**
 * A coupon object.
 */
export class Coupon extends Model {
	/**
	 * The coupon code.
	 *
	 * @type {string}
	 */
	public readonly code: string = '';

	/**
	 * The amount of the discount, must always be numeric.
	 *
	 * @type {string}
	 */
	public readonly amount: string = '';

	/**
	 * The date the coupon was created.
	 *
	 * @type {Date}
	 */
	public readonly dateCreated: Date = new Date();

	/**
	 * The date the coupon was modified.
	 *
	 * @type {Date}
	 */
	public readonly dateModified: Date = new Date();

	/**
	 * The discount type for the coupon.
	 *
	 * @type {string}
	 */
	public readonly discountType: string | DiscountType = '';

	/**
	 * The description of the coupon.
	 *
	 * @type {string}
	 */
	public readonly description: string = '';

	/**
	 * The date the coupon expires.
	 *
	 * @type {Date}
	 */
	public readonly dateExpires: Date = new Date();

	/**
	 * The number of times the coupon has already been used.
	 *
	 * @type {number}
	 */
	public readonly usageCount: Number = 0;

	/**
	 * Flags if the coupon can only be used on its own and not combined with other coupons.
	 *
	 * @type {boolean}
	 */
	public readonly individualUse: boolean = false;

	/**
	 * List of Product IDs that the coupon can be applied to.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly productIds: Array<number> = [];

	/**
	 * List of Product IDs that the coupon cannot be applied to.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly excludedProductIds: Array<number> = [];

	/**
	 * How many times the coupon can be used.
	 *
	 * @type {number}
	 */
	public readonly usageLimit: Number = -1;

	/**
	 * How many times the coupon can be used per customer.
	 *
	 * @type {number}
	 */
	public readonly usageLimitPerUser: Number = -1;

	/**
	 * Max number of items in the cart the coupon can be applied to.
	 *
	 * @type {number}
	 */
	public readonly limitUsageToXItems: Number = -1;

	/**
	 * Flags if the free shipping option requires a coupon. This coupon will enable free shipping.
	 *
	 * @type {boolean}
	 */
	public readonly freeShipping: boolean = false;

	/**
	 * List of Category IDs the coupon applies to.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly productCategories: Array<number> = [];

	/**
	 * List of Category IDs the coupon does not apply to.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly excludedProductCategories: Array<number> = [];

	/**
	 * Flags if the coupon applies to items on sale.
	 *
	 * @type {boolean}
	 */
	public readonly excludeSaleItems: boolean = false;

	/**
	 * The minimum order amount that needs to be in the cart before the coupon applies.
	 *
	 * @type {string}
	 */
	public readonly minimumAmount: string = '';

	/**
	 * The maximum order amount allowed when using the coupon.
	 *
	 * @type {string}
	 */
	public readonly maximumAmount: string = '';

	/**
	 * List of email addresses that can use this coupon.
	 *
	 * @type {ReadonlyArray.<string>}
	 */
	public readonly emailRestrictions: Array<string> = [];

	/**
	 * List of user IDs (or guest emails) that have used the coupon.
	 *
	 * @type {ReadonlyArray.<string>}
	 */
	public readonly usedBy: Array<string> = [];

	/**
	 * The coupon's links.
	 *
	 * @type {ReadonlyArray.<ObjectLinks>}
	 */
	public readonly links: ObjectLinks = {
		collection: [ { href: '' } ],
		self: [ { href: '' } ],
	};

	/**
	 * Creates a new coupon instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< Coupon > ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Returns the repository for interacting with this type of model.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof couponRESTRepository > {
		return couponRESTRepository( httpClient );
	}
}
