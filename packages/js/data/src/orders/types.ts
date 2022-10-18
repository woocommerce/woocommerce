/**
 * External dependencies
 */
import { Schema } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { BaseQueryParams } from '../types';

export type Address = {
	first_name: string;
	last_name: string;
	company: string;
	address_1: string;
	address_2: string;
	city: string;
	state: string;
	postcode: string;
	country: string;
	email: string;
	phone: string;
};

export type OrderTax = {
	id: number;
	total: string;
	subtotal: string;
};

export type OrderTaxLine = {
	id: number;
	rate_code: string;
	rate_id: string;
	label: string;
	compound: boolean;
	tax_total: string;
	shipping_tax_total: string;
};

export type OrderFeeLine = {
	id: number;
	name: string;
	tax_class: string;
	tax_status: string;
	total: string;
	total_tax: string;
	taxes: OrderTax[];
};

export type OrderShippingLine = {
	id: number;
	method_title: string;
	method_id: string;
	total: string;
	total_tax: string;
	taxes: Omit< OrderTax, 'subtotal' >[];
};

export type OrderCoupon = {
	id: number;
	code: string;
	discount: string;
	discount_tax: string;
};

export type OrderMetaData = {
	key: string;
	label: string;
	value: unknown;
};

export type OrderRefund = {
	id: number;
	reason: string;
	total: string;
};

export type OrderLineItem = {
	id: number;
	name: string;
	sku: string;
	product_id: string | number;
	variation_id: number;
	quantity: number;
	tax_class: string;
	price: string;
	subtotal: string;
	subtotal_tax: string;
	total: string;
	total_tax: string;
	taxes: OrderTax[];
	meta_data: OrderMetaData[];
};

export type OrderStatus =
	| 'processing'
	| 'pending'
	| 'on-hold'
	| 'completed'
	| 'cancelled'
	| 'refunded'
	| 'failed';

export type Order< Status = OrderStatus > = Omit< Schema.Post, 'status' > & {
	id: number;
	number: string;
	order_key: string;
	created_via: string;
	status: Status;
	currency: string;
	version: number;
	prices_include_tax: boolean;
	customer_id: number;
	discount_total: string;
	discount_tax: string;
	shipping_total: string;
	shipping_tax: string;
	cart_tax: string;
	total: string;
	total_tax: string;
	billing: Address;
	shipping: Address;
	payment_method: string;
	payment_method_title: string;
	set_paid: boolean;
	transaction_id: string;
	customer_ip_address: string;
	customer_user_agent: string;
	customer_note: string;
	date_completed: string;
	date_paid: string;
	cart_hash: string;
	line_items: OrderLineItem[];
	tax_lines: OrderTaxLine[];
	shipping_lines: OrderShippingLine[];
	fee_lines: OrderFeeLine[];
	coupon_lines: OrderCoupon[];
	refunds: OrderRefund[];
};

export type PartialOrder = Partial< Order > & Pick< Order, 'id' >;

type OrdersQueryStatus =
	| 'any'
	| 'pending'
	| 'processing'
	| 'on-hold'
	| 'completed'
	| 'cancelled'
	| 'refunded'
	| 'failed'
	| 'trash';

export type OrdersQuery< Status = OrdersQueryStatus > = BaseQueryParams<
	keyof Order
> & {
	status: Status;
	customer: number;
	product: number;
	dp: number;
};
