export {};

declare global {
	interface ShipmentProvider {
		label: string;
		icon: string | null;
		value: string;
	}

	interface FulfillmentStatusProps {
		label: string;
		is_fulfilled: boolean;
		background_color: string;
		text_color: string;
	}

	interface Window {
		wcFulfillmentSettings: {
			providers: Record< string, ShipmentProvider >;
			currency_symbols: Record< string, string >;
			fulfillment_statuses: Record< string, FulfillmentStatusProps >;
			order_fulfillment_statuses: Record< string, FulfillmentStatusProps >;
		};
	}
}
