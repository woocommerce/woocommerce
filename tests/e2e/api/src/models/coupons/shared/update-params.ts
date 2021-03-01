/**
 * Coupon properties that can be updated
 */
export type CouponUpdateParams = 'code' | 'amount' | 'description' | 'dateExpires' | 'individualUse'
  | 'usageCount' | 'productIds' | 'excludedProductIds' | 'usageLimit' | 'usageLimitPerUser' | 'limitUsageToXItems'
  | 'freeShipping' | 'productCategories' | 'excludedProductCategories' | 'excludeSaleItems' | 'minimumAmount'
  | 'maximumAmount' | 'emailRestrictions';

/**
 * The discount type of the coupon
 */
export type CouponDiscountTypeUpdateParams = 'percent' | 'fixed_cart' | 'fixed_product';
