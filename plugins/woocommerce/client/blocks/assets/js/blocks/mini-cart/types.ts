/**
 * External dependencies
 */
import { CartResponseTotals } from '@woocommerce/types';

export type IconType = 'cart' | 'bag' | 'bag-alt' | undefined;

export interface ColorItem {
	color: string;
	name?: string;
	slug?: string;
	class?: string;
}
export interface BlockAttributes {
	initialCartItemsCount?: number;
	initialCartTotals?: CartResponseTotals;
	isInitiallyOpen?: boolean;
	colorClassNames?: string;
	style?: Record< string, Record< string, string > >;
	contents: string;
	miniCartIcon?: IconType;
	addToCartBehaviour: string;
	hasHiddenPrice: boolean;
	priceColor: ColorItem;
	iconColor: ColorItem;
	productCountColor: ColorItem;
}
