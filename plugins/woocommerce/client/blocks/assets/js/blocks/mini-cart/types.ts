export type IconType = 'cart' | 'bag' | 'bag-alt' | undefined;
export enum DisplayStyle {
	ICON_ONLY = 'icon_only',
	TEXT_ONLY = 'text_only',
	ICON_AND_TEXT = 'icon_and_text',
}
export type productCountVisibilityType =
	'always' | 'never' | 'greater_than_zero' | undefined;

export interface ColorItem {
	color: string;
	name?: string;
	slug?: string;
	class?: string;
}
