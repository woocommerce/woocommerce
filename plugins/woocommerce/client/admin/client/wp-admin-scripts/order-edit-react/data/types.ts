/**
 * Shared types for the order-edit-react experiment.
 *
 * Mirrors the V4 Orders REST shape that we read from `/wc/v4/orders/{id}`.
 */

export interface OrderAddress {
	first_name?: string;
	last_name?: string;
	company?: string;
	address_1?: string;
	address_2?: string;
	city?: string;
	state?: string;
	postcode?: string;
	country?: string;
	email?: string;
	phone?: string;
}

export interface OrderLineItem {
	id: number;
	name: string;
	product_id?: number;
	variation_id?: number;
	quantity: number;
	subtotal: string;
	total: string;
	price?: string | number;
	sku?: string;
	image?: { src?: string };
}

export interface OrderTaxLine {
	id: number;
	rate_code?: string;
	label?: string;
	tax_total?: string;
	shipping_tax_total?: string;
}

export interface OrderShippingLine {
	id: number;
	method_title?: string;
	total?: string;
}

export interface OrderFeeLine {
	id: number;
	name?: string;
	total?: string;
}

export interface OrderRefund {
	id: number;
	reason?: string;
	total: string;
	date_created?: string;
}

export interface OrderMeta {
	id?: number;
	key: string;
	value: string | number | boolean | null;
}

export interface Order {
	id: number;
	number: string;
	status: string;
	currency: string;
	currency_symbol?: string;
	date_created?: string;
	date_paid?: string | null;
	total: string;
	subtotal?: string;
	total_tax?: string;
	shipping_total?: string;
	discount_total?: string;
	customer_id: number;
	customer_note?: string;
	payment_method?: string;
	payment_method_title?: string;
	transaction_id?: string;
	billing: OrderAddress;
	shipping: OrderAddress;
	line_items: OrderLineItem[];
	shipping_lines: OrderShippingLine[];
	tax_lines: OrderTaxLine[];
	fee_lines: OrderFeeLine[];
	refunds: OrderRefund[];
	meta_data: OrderMeta[];
}

/**
 * A note row from `/wc/v4/order-notes?order_id={id}`.
 * `note_group` distinguishes system events (ORDER_UPDATE, EMAIL_NOTIFICATION,
 * PRODUCT_STOCK, REFUND) from human-authored notes (group is empty/null).
 */
export interface OrderNote {
	id: number;
	author?: string;
	date_created: string;
	note: string;
	customer_note: boolean;
	added_by_user: boolean;
	note_group?: string | null;
}

export interface OrderStatusOption {
	slug: string;
	name: string;
}

/** Subset of fields we read from `/wc/v3/customers/{id}` for the Customer history panel. */
export interface CustomerSummary {
	id: number;
	first_name?: string;
	last_name?: string;
	email?: string;
	orders_count: number;
	total_spent: string;
	date_created?: string;
	avatar_url?: string;
}

/** Status slugs that fire a customer email when transitioned to. */
export const EMAIL_FIRING_STATUSES = new Set( [
	'processing',
	'completed',
	'on-hold',
	'cancelled',
	'refunded',
	'failed',
] );

/** Compute whether transitioning to the given status would fire a customer email. */
export function statusFiresEmail( newStatus: string, currentStatus: string ): boolean {
	if ( newStatus === currentStatus ) {
		return false;
	}
	return EMAIL_FIRING_STATUSES.has( newStatus );
}
