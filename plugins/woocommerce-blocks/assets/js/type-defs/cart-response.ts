/* eslint-disable camelcase -- API responses have camelcase properties */
export interface CurrencyResponseInfo {
	currency_code: string;
	currency_symbol: string;
	currency_minor_unit: number;
	currency_decimal_separator: string;
	currency_thousand_separator: string;
	currency_prefix: string;
	currency_suffix: string;
}

export interface CartResponseTotalsItem extends CurrencyResponseInfo {
	total_discount: string;
	total_discount_tax: string;
}

export interface CartResponseCouponItem {
	code: string;
	discount_type: string;
	totals: CartResponseTotalsItem;
}

export interface ResponseFirstNameLastName {
	first_name: string;
	last_name: string;
}

export interface ResponseBaseAddress {
	address_1: string;
	address_2: string;
	city: string;
	state: string;
	postcode: string;
	country: string;
}

export interface ShippingRateItemItem {
	key: string;
	name: string;
	quantity: number;
}

export interface MetaKeyValue {
	key: string;
	value: string;
}

export type ExtensionsData =
	| Record< string, unknown >
	| Record< string, never >;

export interface CartResponseShippingRateItemShippingRate
	extends CurrencyResponseInfo {
	rate_id: string;
	name: string;
	description: string;
	delivery_time: string;
	price: string;
	taxes: string;
	instance_id: number;
	method_id: string;
	meta_data: Array< MetaKeyValue >;
	selected: boolean;
}

export interface CartResponseShippingRateItem {
	package_id: number;
	name: string;
	destination: ResponseBaseAddress;
	items: Array< ShippingRateItemItem >;
	shipping_rates: Array< CartResponseShippingRateItemShippingRate >;
}

export interface CartResponseShippingAddress
	extends ResponseBaseAddress,
		ResponseFirstNameLastName {
	company: string;
}

export interface CartResponseBillingAddress
	extends CartResponseShippingAddress {
	phone: string;
	email: string;
}

export interface CartResponseImageItem {
	id: number;
	src: string;
	thumbnail: string;
	srcset: string;
	sizes: string;
	name: string;
	alt: string;
}

export interface CartResponseVariationItem {
	attribute: string;
	value: string;
}

export interface CartResponseItemPrices extends CurrencyResponseInfo {
	price: string;
	regular_price: string;
	sale_price: string;
	price_range: null | { min_amount: string; max_amount: string };
	raw_prices: {
		precision: number;
		price: string;
		regular_price: string;
		sale_price: string;
	};
}

export interface CartResponseItemTotals extends CurrencyResponseInfo {
	line_subtotal: string;
	line_subtotal_tax: string;
	line_total: string;
	line_total_tax: string;
}

export interface CartResponseItem {
	key: string;
	id: number;
	quantity: number;
	quantity_limit: number;
	name: string;
	summary: string;
	short_description: string;
	description: string;
	sku: string;
	low_stock_remaining: string;
	backorders_allowed: boolean;
	show_backorder_badge: boolean;
	sold_individually: boolean;
	permalink: string;
	images: Array< CartResponseImageItem >;
	variation: Array< CartResponseVariationItem >;
	prices: CartResponseItemPrices;
	totals: CartResponseItemTotals;
}

export interface CartResponseTotalsTaxLineItem {
	name: string;
	price: string;
}

export interface CartResponseFeeItemTotals extends CurrencyResponseInfo {
	total: string;
	total_tax: string;
}

export type CartResponseFeeItem = {
	id: string;
	name: string;
	totals: CartResponseFeeItemTotals;
};

export interface CartResponseTotals extends CurrencyResponseInfo {
	total_items: string;
	total_items_tax: string;
	total_fees: string;
	total_fees_tax: string;
	total_discount: string;
	total_discount_tax: string;
	total_shipping: string;
	total_shipping_tax: string;
	total_price: string;
	total_tax: string;
	tax_lines: Array< CartResponseTotalsTaxLineItem >;
}

export interface CartResponseErrorItem {
	code: string;
	message: string;
}

export interface CartResponseExtensionItem {
	[ key: string ]: unknown;
}

export interface CartResponse {
	coupons: Array< CartResponseCouponItem >;
	shipping_rates: Array< CartResponseShippingRateItem >;
	shipping_address: CartResponseShippingAddress;
	billing_address: CartResponseBillingAddress;
	items: Array< CartResponseItem >;
	items_count: number;
	items_weight: number;
	needs_payment: boolean;
	needs_shipping: boolean;
	has_calculated_shipping: boolean;
	fees: Array< CartResponseFeeItem >;
	totals: CartResponseTotals;
	errors: Array< CartResponseErrorItem >;
	payment_requirements: Array< unknown >;
	extensions: ExtensionsData;
}
