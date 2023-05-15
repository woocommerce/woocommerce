export type HighlightSides = 'A' | 'B' | 'C';

export type ShippingDimensionsImageProps = React.SVGProps< SVGSVGElement > & {
	highlight?: HighlightSides;
	labels?: {
		A?: string | number;
		B?: string | number;
		C?: string | number;
	};
};
