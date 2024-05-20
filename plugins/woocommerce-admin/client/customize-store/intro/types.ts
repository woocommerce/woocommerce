export type ColorPalette = {
	title: string;
	primary: string;
	primary_border?: string;
	secondary: string;
	secondary_border?: string;
};
export type ThemeCard = {
	// placeholder props, possibly take reference from https://github.com/Automattic/wp-calypso/blob/1f1b79210c49ef0d051f8966e24122229a334e29/packages/design-picker/src/components/theme-card/index.tsx#L32
	slug: string;
	name: string;
	description: string;
	thumbnail_url: string;
	is_active: boolean;
	link_url?: string;
	price: string;
	color_palettes: ColorPalette[];
	total_palettes: number;
};
