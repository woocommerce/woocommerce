export interface Order {
	id: number;
	parent_id: number;
	status: string;
	currency: string;
	version: string;
	prices_include_tax: boolean;
	date_created: Date;
	date_modified: Date;
	discount_total: string;
	discount_tax: string;
	shipping_total: string;
	shipping_tax: string;
	cart_tax: string;
	total: string;
	total_tax: string;
	customer_id: number;
	order_key: string;
	billing: Ing;
	shipping: Ing;
	payment_method: string;
	payment_method_title: string;
	transaction_id: string;
	customer_ip_address: string;
	customer_user_agent: string;
	created_via: string;
	customer_note: string;
	date_completed: null;
	date_paid: Date;
	cart_hash: string;
	number: string;
	meta_data: OrderMetaDatum[];
	line_items: LineItem[];
	shipping_lines: ShippingLine[];
	refunds: OrderRefund[];
	payment_url: string;
	is_editable: boolean;
	needs_payment: boolean;
	needs_processing: boolean;
	date_created_gmt: Date;
	date_modified_gmt: Date;
	date_completed_gmt: null;
	date_paid_gmt: Date;
	currency_symbol: string;
	_links: Links;
}

export interface OrderRefund {
	id: number;
	reason: string;
	total: string;
}

export interface Refund {
	id: number;
	parent_id: number;
	date_created: Date;
	date_created_gmt: Date;
	amount: string;
	reason: string;
	refunded_by: number;
	refunded_payment: boolean;
	meta_data: OrderMetaDatum[];
	line_items: LineItem[];
	shipping_lines: ShippingLine[];
	tax_lines: OrderMetaDatum[];
	fee_lines: OrderMetaDatum[];
	_links: Links;
}

export interface Links {
	self: Self[];
	collection: Collection[];
	email_templates: EmailTemplate[];
	customer: Collection[];
}

export interface Collection {
	href: string;
}

export interface EmailTemplate {
	embeddable: boolean;
	href: string;
}

export interface Self {
	href: string;
	targetHints: TargetHints;
}

export interface TargetHints {
	allow: string[];
}

export interface Ing {
	first_name: string;
	last_name: string;
	company: string;
	address_1: string;
	address_2: string;
	city: string;
	state: string;
	postcode: string;
	country: string;
	email?: string;
	phone: string;
}

export interface LineItem {
	id: number;
	name: string;
	product_id: number;
	variation_id: number;
	quantity: number;
	tax_class: string;
	subtotal: string;
	subtotal_tax: string;
	total: string;
	total_tax: string;
	sku: string;
	price: number;
	image: Image;
	parent_name: null;
}

export interface Image {
	id: string;
	src: string;
}

export interface OrderMetaDatum {
	id: number;
	key: string;
	value: string;
}

export interface ShippingLine {
	id: number;
	method_title: string;
	method_id: string;
	instance_id: string;
	total: string;
	total_tax: string;
	tax_status: string;
	meta_data: ShippingLineMetaDatum[];
}

export interface ShippingLineMetaDatum {
	id: number;
	key: string;
	value: string;
	display_key: string;
	display_value: string;
}

export interface Fulfillment {
	id?: number;
	fulfillment_id?: number;
	entity_type: string;
	entity_id: string;
	status: string;
	is_fulfilled: boolean;
	date_updated?: Date;
	meta_data: MetaDatum[];
}

export interface MetaDatum {
	id: number;
	key: string;
	value: string | number | boolean | object | null;
}

export interface FulfillmentItem {
	item_id: number;
	qty: number;
}
