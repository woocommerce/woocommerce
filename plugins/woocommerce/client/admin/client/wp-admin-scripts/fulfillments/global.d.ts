export {};

declare global {
	interface ShipmentProvider {
		label: string;
		icon: string | null;
		value: string;
	}

	interface FulfillmentStatus {
		label: string;
		is_fulfilled: boolean;
		background_color: string;
		text_color: string;
	}

	interface Window {
		wcFulfillmentSettings: {
			providers: Record< string, ShipmentProvider >;
			currency_symbols: Record< string, string >;
			statuses: Record< string, FulfillmentStatus >;
		};
	}
}
