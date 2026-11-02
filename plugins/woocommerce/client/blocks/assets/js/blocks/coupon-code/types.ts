/**
 * External dependencies
 */
import type { BlockEditProps as WPBlockEditProps } from '@wordpress/blocks';

interface ProductItem {
	id: number;
	title: string;
}

export interface CouponCodeAttributes {
	couponCode: string;
	source: 'createNew' | 'existing';
	discountType: string;
	amount: number;
	expiryDay: number;
	freeShipping: boolean;
	usageLimit: number;
	usageLimitPerUser: number;
	minimumAmount: string;
	maximumAmount: string;
	individualUse: boolean;
	excludeSaleItems: boolean;
	productIds: ProductItem[];
	excludedProductIds: ProductItem[];
	productCategoryIds: ProductItem[];
	excludedProductCategoryIds: ProductItem[];
	emailRestrictions: string;
	align?: string;
}

/**
 * Block edit props.
 */
export type BlockEditProps = WPBlockEditProps< CouponCodeAttributes >;

/**
 * Block save props.
 */
export type BlockSaveProps = {
	attributes: CouponCodeAttributes;
};
