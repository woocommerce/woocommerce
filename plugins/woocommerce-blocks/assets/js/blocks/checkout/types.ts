export type InnerBlockTemplate = [
	string,
	Record< string, unknown >,
	InnerBlockTemplate[] | undefined
];

export interface Attributes extends Record< string, boolean | number > {
	hasDarkControls: boolean;
	showCompanyField: boolean;
	requireCompanyField: boolean;
	showApartmentField: boolean;
	requireApartmentField: boolean;
	showPhoneField: boolean;
	requirePhoneField: boolean;
	// Deprecated.
	showOrderNotes: boolean;
	showPolicyLinks: boolean;
	showReturnToCart: boolean;
	showRateAfterTaxName: boolean;
	cartPageId: number;
}
