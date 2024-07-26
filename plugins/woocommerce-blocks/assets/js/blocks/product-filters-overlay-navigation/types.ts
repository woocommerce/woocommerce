type BorderRadius = {
	bottomLeft: string;
	bottomRight: string;
	topLeft: string;
	topRight: string;
};
type BorderSide = {
	color: string;
	width: string;
};

export type BlockAttributes = {
	navigationStyle: 'label-and-icon' | 'label-only' | 'icon-only';
	buttonStyle: string;
	iconSize?: number;
	style: {
		border?: {
			radius?: string | BorderRadius;
			width?: string;
			top?: BorderSide;
			bottom?: BorderSide;
			left?: BorderSide;
			right?: BorderSide;
		};
		spacing?: {
			blockGap?: string;
			margin?: {
				top?: string;
				right?: string;
				bottom?: string;
				left?: string;
			};
			padding?: {
				top?: string;
				right?: string;
				bottom?: string;
				left?: string;
			};
		};
		typography?: {
			fontSize?: string;
			lineHeight?: number;
			fontStyle?: string;
			fontWeight?: string;
			letterSpacing?: string;
			textDecoration?: string;
			textTransform?: string;
		};
	};
};
