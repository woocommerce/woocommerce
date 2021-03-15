/**
 * Coupon properties that can be updated
 */
export type CouponUpdateParams = 'code' | 'amount' | 'description' | 'discountType' | 'dateExpires' | 'individualUse'
  | 'usageCount' | 'productIds' | 'excludedProductIds' | 'usageLimit' | 'usageLimitPerUser' | 'limitUsageToXItems'
  | 'freeShipping' | 'productCategories' | 'excludedProductCategories' | 'excludeSaleItems' | 'minimumAmount'
  | 'maximumAmount' | 'emailRestrictions';
