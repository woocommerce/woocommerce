export type InnerBlockTemplate = [
	string,
	Record< string, unknown >,
	InnerBlockTemplate[] | undefined
];

export interface Attributes {
	isPreview: boolean;
	isShippingCalculatorEnabled: boolean;
	hasDarkControls: boolean;
	showRateAfterTaxName: boolean;
	checkoutPageId: number;
}
