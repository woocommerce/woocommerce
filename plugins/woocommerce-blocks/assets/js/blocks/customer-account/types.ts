export interface Attributes {
	className?: string;
	displayStyle: DisplayStyle;
	iconStyle: IconStyle;
}

export enum DisplayStyle {
	ICON_AND_TEXT = 'icon_and_text',
	TEXT_ONLY = 'text_only',
	ICON_ONLY = 'icon_only',
}

export enum IconStyle {
	DEFAULT = 'default',
	ALT = 'alt',
}
