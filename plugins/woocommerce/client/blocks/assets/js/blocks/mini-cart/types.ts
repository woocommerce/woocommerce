/**
 * External dependencies
 */
import { CartResponseTotals } from '@woocommerce/types';

export type IconType = 'cart' | 'bag' | 'bag-alt' | undefined;
export type productCountVisibilityType =
	| 'always'
	| 'never'
	| 'greater_than_zero'
	| undefined;

export enum DisplayStyle {
	ICON_AND_TEXT = 'icon_and_text',
	TEXT_ONLY = 'text_only',
	ICON_ONLY = 'icon_only',
}

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
	displayStyle?: DisplayStyle;
	addToCartBehaviour: string;
	onCartClickBehaviour: string;
	hasHiddenPrice: boolean;
	priceColor: ColorItem;
	iconColor: ColorItem;
	productCountColor: ColorItem;
	productCountVisibility?: productCountVisibilityType;
}
