export type SettingDefinition = {
	default: string;
	description: string;
	id: string;
	label: string;
	placeholder: string;
	tip: string;
	type: string;
	value: string;
};

export type PaymentGateway = {
	id: string;
	title: string;
	description: string;
	order: number | '';
	enabled: boolean;
	method_title: string;
	method_description: string;
	method_supports: string[];
	settings: Record< string, SettingDefinition >;
};

export type PluginsState = {
	paymentGateways: PaymentGateway[];
	requesting: Record< string, boolean >;
	errors: Record< string, RestApiError >;
};

interface RestApiErrorData {
	status?: number;
}

export type RestApiError = {
	code: string;
	data: RestApiErrorData;
	message: string;
};

export type SelectorKeysWithActions =
	| 'getPaymentGateways'
	| 'getPaymentGateway'
	| 'updatePaymentGateway';

// Type for the basic selectors built into @wordpress/data, note these
// types define the interface for the public selectors, so state is not an
// argument.
export type WPDataSelectors = {
	hasStartedResolution: ( selector: string, args?: string[] ) => boolean;
	hasFinishedResolution: ( selector: string, args?: string[] ) => boolean;
	isResolving: ( selector: string, args: string[] ) => boolean;
};

export type WPDataActions = {
	startResolution: ( selector: string, args?: string[] ) => void;
	finishResolution: ( selector: string, args?: string[] ) => void;
};

// Omitting state from selector parameter
export type WPDataSelector< T > = T extends (
	state: infer S,
	...args: infer A
) => infer R
	? ( ...args: A ) => R
	: T;
