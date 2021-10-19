"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.createCouponTransformer = void 0;
var framework_1 = require("../../../framework");
/**
 * Creates a transformer for a coupon object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createCouponTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.IgnorePropertyTransformation(['date_created', 'date_modified']),
        new framework_1.PropertyTypeTransformation({
            code: framework_1.PropertyType.String,
            amount: framework_1.PropertyType.String,
            dateCreated: framework_1.PropertyType.Date,
            dateModified: framework_1.PropertyType.Date,
            discountType: framework_1.PropertyType.String,
            dateExpires: framework_1.PropertyType.Date,
            usageCount: framework_1.PropertyType.Integer,
            individualUse: framework_1.PropertyType.Boolean,
            usageLimit: framework_1.PropertyType.Integer,
            usageLimitPerUser: framework_1.PropertyType.Integer,
            limitUsageToXItems: framework_1.PropertyType.Integer,
            freeShipping: framework_1.PropertyType.Boolean,
            excludeSaleItems: framework_1.PropertyType.Boolean,
            minimumAmount: framework_1.PropertyType.String,
            maximumAmount: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
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
        }),
    ]);
}
exports.createCouponTransformer = createCouponTransformer;
