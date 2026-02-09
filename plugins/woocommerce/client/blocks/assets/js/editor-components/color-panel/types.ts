export interface ColorSetting {
	colorValue: string | undefined;
	onColorChange: ( value: string ) => void;
	label: string;
	resetAllFilter: () => void;
}

export interface ColorAttributes {
	[ key: string ]: {
		[ key: string ]: string;
	};
}

export interface CustomColorsMap {
	[ key: string ]: {
		label: string;
		context: string;
	};
}

export interface ColorPaletteOption {
	name: string;
	slug: string | undefined;
	color: string;
}

export interface GradientPaletteOption {
	name: string;
	gradient: string;
	slug: string;
}

interface ColorGradientOptionsColorItem {
	name: string;
	colors: ColorPaletteOption[];
}

interface ColorGradientOptionsGradientItem {
	name: string;
	gradients: GradientPaletteOption[];
}

export interface ColorGradientOptionsItems {
	colors: [ ColorGradientOptionsColorItem ];
	gradients: [ ColorGradientOptionsGradientItem ];
}
