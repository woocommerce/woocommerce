export type IconType = 'cart' | 'bag' | 'bag-alt';

export interface BlockAttributes {
	isInitiallyOpen?: boolean;
	colorClassNames?: string;
	style?: Record< string, Record< string, string > >;
	contents: string;
	miniCartIcon?: IconType;
	addToCartBehaviour: string;
	hasHiddenPrice: boolean;
}
