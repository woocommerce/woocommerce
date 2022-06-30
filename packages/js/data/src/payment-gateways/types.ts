/**
 * Internal dependencies
 */

export type SettingDefinition = {
	default: string;
	description: string;
	id: string;
	label: string;
	placeholder: string;
	tip: string;
	type: string;
	value: string;
	is_dismissed: string;
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
	settings: SettingDefinition;
	settings_url: string;
};

export type PluginsState = {
	paymentGateways: PaymentGateway[];
	isUpdating: boolean;
	errors: Record< string, unknown >;
};
