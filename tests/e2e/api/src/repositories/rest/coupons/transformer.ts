import {
	IgnorePropertyTransformation,
	KeyChangeTransformation,
	ModelTransformer,
	PropertyType,
	PropertyTypeTransformation,
} from '../../../framework';

import { Coupon } from '../../../models';

/**
 * Creates a transformer for a coupon object.
 *
 * @return {ModelTransformer} The created transformer.
 */
export function createCouponTransformer(): ModelTransformer< Coupon > {
	return new ModelTransformer(
		[
			new IgnorePropertyTransformation( [ 'date_created', 'date_modified' ] ),
			new PropertyTypeTransformation(
				{
					code: PropertyType.String,
					amount: PropertyType.String,
					dateCreated: PropertyType.Date,
					dateModified: PropertyType.Date,
					discountType: PropertyType.String,
					dateExpires: PropertyType.Date,
					usageCount: PropertyType.Integer,
					individualUse: PropertyType.Boolean,
					usageLimit: PropertyType.Integer,
					usageLimitPerUser: PropertyType.Integer,
					limitUsageToXItems: PropertyType.Integer,
					freeShipping: PropertyType.Boolean,
					excludeSaleItems: PropertyType.Boolean,
					minimumAmount: PropertyType.String,
					maximumAmount: PropertyType.String,
				},
			),
			new KeyChangeTransformation< Coupon >(
				{
					dateCreated: 'date_created_gmt',
					dateModified: 'date_modified_gmt',
					discountType: 'discount_type',
					dateExpires: 'date_expires',
					usageCount: 'usage_count',
					individualUse: 'individual_use',
					productIds: 'product_ids',
					excludedProductIds: 'excluded_product_ids',
					usageLimit: 'usage_limit',
					usageLimitPerUser: 'usage_limit_per_user',
					limitUsageToXItems: 'limit_usage_to_x_items',
					freeShipping: 'free_shipping',
					productCategories: 'product_categories',
					excludedProductCategories: 'excluded_product_categories',
					excludeSaleItems: 'exclude_sale_items',
					minimumAmount: 'minimum_amount',
					maximumAmount: 'maximum_amount',
					emailRestrictions: 'email_restrictions',
					usedBy: 'used_by',
					links: '_links',
				},
			),
		],
	);
}
