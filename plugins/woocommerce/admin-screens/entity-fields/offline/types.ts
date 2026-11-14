/**
 * Shipping method options, grouped by shipping method title, each a rate ID mapped to its label.
 */
export type ShippingMethodOptions = Record< string, Record< string, string > >;

/**
 * A setting as the v3 endpoint returns it. Edited records hold the plain value instead.
 */
export interface GatewaySetting< Value > {
	value: Value;
	options?: ShippingMethodOptions;
}

export interface OfflineGatewaySettings {
	id: string;
	enabled: boolean;
	title: string;
	description: string;
	settings: {
		instructions?: string | GatewaySetting< string >;
		enable_for_methods?: string[] | GatewaySetting< unknown >;
		enable_for_virtual?: string | GatewaySetting< string >;
	};
}
