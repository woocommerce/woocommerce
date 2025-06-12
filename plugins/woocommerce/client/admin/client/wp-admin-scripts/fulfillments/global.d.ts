
declare global {
	interface Window {
		wcFulfillmentSettings: {
			providers: Record< string, ShipmentProvider >;
			currency_symbols: Record< string, string >;
			statuses: Record< string, string >;
		};
	}
}

export interface ShipmentProvider {
	label: string;
	icon: string | null;
	value: string;
}
