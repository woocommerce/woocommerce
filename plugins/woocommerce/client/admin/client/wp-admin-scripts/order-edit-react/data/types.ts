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

export interface OrderLineItemMeta {
	id?: number;
	key: string;
	value: string | number;
	/** Sanitized/decoded key for display (e.g. "Size" for `pa_size`). */
	display_key?: string;
	/** Sanitized/decoded value for display. */
	display_value?: string;
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
	meta_data?: OrderLineItemMeta[];
}

/**
 * Filter a line item's meta_data down to the entries we should show to
 * a merchant: skip internal keys (prefixed with `_`) and skip entries
 * without a usable display value.
 */
export function publicLineItemMeta( item: {
	meta_data?: OrderLineItemMeta[];
} ): OrderLineItemMeta[] {
	if ( ! item.meta_data ) {
		return [];
	}
	return item.meta_data.filter(
		( m ) =>
			m.key && ! m.key.startsWith( '_' ) && ( m.display_value || m.value )
	);
}

/** Render a single meta entry as `Key: Value`. */
export function formatLineItemMeta( meta: OrderLineItemMeta ): string {
	const key = meta.display_key || meta.key;
	const value = String( meta.display_value ?? meta.value );
	return `${ key }: ${ value }`;
}

/**
 * Common variant-attribute names (lowercased). Any line-item meta whose key
 * matches one of these is treated as a variation attribute and rendered as
 * a badge. Anything else (e.g. "Engraving", "Gift wrap", "delivery_notes")
 * is treated as ordinary custom-field meta.
 *
 * This is a pragmatic whitelist: real variant attributes can be arbitrary
 * (a merchant might add "Theme" or "Texture"), but identifying them
 * reliably from a line item alone — without the parent product's attribute
 * config — isn't possible. The whitelist covers the common commerce
 * vocabulary; uncommon attributes will fall through to the "other" bucket
 * and render as text, which is the safer failure mode.
 */
const KNOWN_VARIANT_ATTRIBUTE_NAMES = new Set( [
	'size',
	'color',
	'colour',
	'material',
	'style',
	'pattern',
	'length',
	'width',
	'height',
	'depth',
	'weight',
	'capacity',
	'volume',
	'brand',
	'model',
	'flavor',
	'flavour',
	'scent',
	'fragrance',
	'finish',
	'texture',
	'fabric',
	'fit',
	'type',
] );

/**
 * Decide whether a line-item meta entry is a variation attribute (badge
 * treatment) or a custom field (text treatment).
 *
 * Rules, in order:
 *  - Keys prefixed `pa_` are global attribute taxonomy slugs → variant.
 *  - Keys whose lowercased value is in the known-attribute-name whitelist
 *    above → variant.
 *  - Everything else (including snake_case custom fields like
 *    `gift_message`) → other.
 */
export function isVariationAttribute( meta: OrderLineItemMeta ): boolean {
	if ( ! meta.key ) {
		return false;
	}
	if ( meta.key.startsWith( '_' ) ) {
		return false;
	}
	if ( meta.key.startsWith( 'pa_' ) ) {
		return true;
	}
	const normalized = meta.key.trim().toLowerCase();
	return KNOWN_VARIANT_ATTRIBUTE_NAMES.has( normalized );
}

/**
 * Split a line item's public meta into variation attributes vs everything
 * else, preserving the original `index` (position in `meta_data`) so callers
 * editing other meta can target the right entry by index.
 */
export function splitLineItemMeta( item: {
	meta_data?: OrderLineItemMeta[];
} ): {
	variants: Array< { meta: OrderLineItemMeta; index: number } >;
	other: Array< { meta: OrderLineItemMeta; index: number } >;
} {
	const variants: Array< { meta: OrderLineItemMeta; index: number } > = [];
	const other: Array< { meta: OrderLineItemMeta; index: number } > = [];
	( item.meta_data || [] ).forEach( ( meta, index ) => {
		if ( ! meta.key || meta.key.startsWith( '_' ) ) {
			return;
		}
		if ( isVariationAttribute( meta ) ) {
			variants.push( { meta, index } );
		} else {
			other.push( { meta, index } );
		}
	} );
	return { variants, other };
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

/** Product image entry from `/wc/v3/products` responses. */
export interface ProductImage {
	id?: number;
	src?: string;
	alt?: string;
	name?: string;
}

/** Variation attribute entry from `/wc/v3/products/{id}/variations` responses. */
export interface ProductVariationAttribute {
	id?: number;
	name?: string;
	option?: string;
}

/**
 * Search hit from `/wc/v3/products?search=...`. The product picker modal
 * uses these to render result rows. Variable products carry their variation
 * IDs so we can drill in with a second fetch.
 */
export interface ProductSearchResult {
	id: number;
	name: string;
	type: string;
	status?: string;
	sku?: string;
	price: string;
	regular_price?: string;
	sale_price?: string;
	on_sale?: boolean;
	stock_status?: string;
	images?: ProductImage[];
	variations?: number[];
}

/**
 * Variation row from `/wc/v3/products/{product_id}/variations`. We pick one
 * and add it as a line item with `variation_id` set.
 */
export interface ProductVariationResult {
	id: number;
	parent_id?: number;
	name?: string;
	sku?: string;
	price: string;
	regular_price?: string;
	sale_price?: string;
	on_sale?: boolean;
	stock_status?: string;
	image?: ProductImage;
	attributes?: ProductVariationAttribute[];
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
export function statusFiresEmail(
	newStatus: string,
	currentStatus: string
): boolean {
	if ( newStatus === currentStatus ) {
		return false;
	}
	return EMAIL_FIRING_STATUSES.has( newStatus );
}

/**
 * Detect whether an order note is system-generated vs user-authored.
 *
 * The WC v3 REST schema declares `added_by_user` but the controller's
 * `prepare_item_for_response` never actually includes it in the response
 * (see class-wc-rest-order-notes-controller.php). We discriminate using
 * the `author` field instead — `WC_Order::add_order_note` sets the
 * comment author to "WooCommerce" for system notes (when
 * `$added_by_user === false`) and to the current user's display name when
 * `$added_by_user === true`.
 */
export function isSystemNote( note: { author?: string } ): boolean {
	if ( ! note.author ) {
		return true;
	}
	const a = note.author.toLowerCase();
	return a === 'woocommerce' || a === 'system';
}
