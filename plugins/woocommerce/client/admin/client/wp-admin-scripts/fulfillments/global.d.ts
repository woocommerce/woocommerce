export {};

declare global {
	interface ShipmentProvider {
		label: string;
		icon: string | null;
		value: string;
	}
	interface Window {
		wcFulfillmentSettings: {
			providers: Record< string, ShipmentProvider >;
			currency_symbols: Record< string, string >;
			statuses: Record< string, string >;
		};
	}
}
