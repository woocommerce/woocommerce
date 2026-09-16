export type IconType = 'cart' | 'bag' | 'bag-alt' | undefined;
export type productCountVisibilityType =
	| 'always'
	| 'never'
	| 'greater_than_zero'
	| undefined;

export interface ColorItem {
	color: string;
	name?: string;
	slug?: string;
	class?: string;
}
