export type HighlightSides = 'A' | 'B' | 'C';

export type ShippingDimensionsImageProps = React.SVGProps< SVGSVGElement > & {
	highlight?: HighlightSides;
};
