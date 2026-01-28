/**
 * Consumer key interface for WooCommerce REST API authentication.
 */
export interface ConsumerKey {
	name: string;
	key: string;
	secret: string;
}

/**
 * Standard WooCommerce REST API response wrapper.
 */
export interface WooCommerceApiResponse< T > {
	data: T;
	headers: Record< string, string >;
	status: number;
}

/**
 * WooCommerce REST API error response.
 */
export interface WooCommerceApiError {
	code: string;
	message: string;
	data?: {
		status: number;
		params?: Record< string, string >;
		details?: Record< string, unknown >;
	};
}

/**
 * Basic product type from WooCommerce REST API.
 */
export interface WooCommerceProduct {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	type: 'simple' | 'grouped' | 'external' | 'variable';
	status: 'draft' | 'pending' | 'private' | 'publish';
	featured: boolean;
	catalog_visibility: 'visible' | 'catalog' | 'search' | 'hidden';
	description: string;
	short_description: string;
	sku: string;
	price: string;
	regular_price: string;
	sale_price: string;
	on_sale: boolean;
	purchasable: boolean;
	total_sales: number;
	virtual: boolean;
	downloadable: boolean;
	tax_status: 'taxable' | 'shipping' | 'none';
	tax_class: string;
	manage_stock: boolean;
	stock_quantity: number | null;
	stock_status: 'instock' | 'outofstock' | 'onbackorder';
	backorders: 'no' | 'notify' | 'yes';
	backorders_allowed: boolean;
	backordered: boolean;
	weight: string;
	dimensions: {
		length: string;
		width: string;
		height: string;
	};
	categories: Array< {
		id: number;
		name: string;
		slug: string;
	} >;
	tags: Array< {
		id: number;
		name: string;
		slug: string;
	} >;
	images: Array< {
		id: number;
		src: string;
		name: string;
		alt: string;
	} >;
	attributes: Array< {
		id: number;
		name: string;
		position: number;
		visible: boolean;
		variation: boolean;
		options: string[];
	} >;
	variations: number[];
	date_created: string;
	date_modified: string;
}

/**
 * Basic order type from WooCommerce REST API.
 */
export interface WooCommerceOrder {
	id: number;
	parent_id: number;
	number: string;
	order_key: string;
	created_via: string;
	version: string;
	status:
		| 'pending'
		| 'processing'
		| 'on-hold'
		| 'completed'
		| 'cancelled'
		| 'refunded'
		| 'failed'
		| 'trash';
	currency: string;
	date_created: string;
	date_modified: string;
	discount_total: string;
	discount_tax: string;
	shipping_total: string;
	shipping_tax: string;
	cart_tax: string;
	total: string;
	total_tax: string;
	prices_include_tax: boolean;
	customer_id: number;
	customer_ip_address: string;
	customer_user_agent: string;
	customer_note: string;
	billing: WooCommerceAddress;
	shipping: WooCommerceAddress;
	payment_method: string;
	payment_method_title: string;
	transaction_id: string;
	line_items: WooCommerceLineItem[];
	tax_lines: WooCommerceTaxLine[];
	shipping_lines: WooCommerceShippingLine[];
	fee_lines: WooCommerceFeeLine[];
	coupon_lines: WooCommerceCouponLine[];
	refunds: WooCommerceRefund[];
}

/**
 * Address type used in orders.
 */
export interface WooCommerceAddress {
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
	phone?: string;
}

/**
 * Line item in an order.
 */
export interface WooCommerceLineItem {
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
	taxes: Array< {
		id: number;
		total: string;
		subtotal: string;
	} >;
	sku: string;
	price: number;
}

/**
 * Tax line in an order.
 */
export interface WooCommerceTaxLine {
	id: number;
	rate_code: string;
	rate_id: number;
	label: string;
	compound: boolean;
	tax_total: string;
	shipping_tax_total: string;
}

/**
 * Shipping line in an order.
 */
export interface WooCommerceShippingLine {
	id: number;
	method_title: string;
	method_id: string;
	instance_id: string;
	total: string;
	total_tax: string;
	taxes: Array< {
		id: number;
		total: string;
	} >;
}

/**
 * Fee line in an order.
 */
export interface WooCommerceFeeLine {
	id: number;
	name: string;
	tax_class: string;
	tax_status: 'taxable' | 'none';
	total: string;
	total_tax: string;
	taxes: Array< {
		id: number;
		total: string;
		subtotal: string;
	} >;
}

/**
 * Coupon line in an order.
 */
export interface WooCommerceCouponLine {
	id: number;
	code: string;
	discount: string;
	discount_tax: string;
}

/**
 * Refund reference in an order.
 */
export interface WooCommerceRefund {
	id: number;
	reason: string;
	total: string;
}

/**
 * Basic customer type from WooCommerce REST API.
 */
export interface WooCommerceCustomer {
	id: number;
	date_created: string;
	date_modified: string;
	email: string;
	first_name: string;
	last_name: string;
	role: string;
	username: string;
	billing: WooCommerceAddress;
	shipping: WooCommerceAddress;
	is_paying_customer: boolean;
	avatar_url: string;
}

/**
 * Basic coupon type from WooCommerce REST API.
 */
export interface WooCommerceCoupon {
	id: number;
	code: string;
	amount: string;
	date_created: string;
	date_modified: string;
	discount_type: 'percent' | 'fixed_cart' | 'fixed_product';
	description: string;
	date_expires: string | null;
	usage_count: number;
	individual_use: boolean;
	product_ids: number[];
	excluded_product_ids: number[];
	usage_limit: number | null;
	usage_limit_per_user: number | null;
	limit_usage_to_x_items: number | null;
	free_shipping: boolean;
	product_categories: number[];
	excluded_product_categories: number[];
	exclude_sale_items: boolean;
	minimum_amount: string;
	maximum_amount: string;
	email_restrictions: string[];
	used_by: string[];
}

/**
 * Shipping zone type from WooCommerce REST API.
 */
export interface WooCommerceShippingZone {
	id: number;
	name: string;
	order: number;
}

/**
 * Shipping method type from WooCommerce REST API.
 */
export interface WooCommerceShippingMethod {
	instance_id: number;
	title: string;
	order: number;
	enabled: boolean;
	method_id: string;
	method_title: string;
	method_description: string;
	settings: Record<
		string,
		{
			id: string;
			label: string;
			description: string;
			type: string;
			value: string;
			default: string;
			tip: string;
			placeholder: string;
		}
	>;
}

/**
 * Payment gateway type from WooCommerce REST API.
 */
export interface WooCommercePaymentGateway {
	id: string;
	title: string;
	description: string;
	order: number;
	enabled: boolean;
	method_title: string;
	method_description: string;
	method_supports: string[];
	settings: Record<
		string,
		{
			id: string;
			label: string;
			description: string;
			type: string;
			value: string;
			default: string;
			tip: string;
			placeholder: string;
		}
	>;
}

/**
 * Tax rate type from WooCommerce REST API.
 */
export interface WooCommerceTaxRate {
	id: number;
	country: string;
	state: string;
	postcode: string;
	city: string;
	postcodes: string[];
	cities: string[];
	rate: string;
	name: string;
	priority: number;
	compound: boolean;
	shipping: boolean;
	order: number;
	class: string;
}

/**
 * Setting option type from WooCommerce REST API.
 */
export interface WooCommerceSettingOption {
	id: string;
	label: string;
	description: string;
	type: string;
	default: string;
	options?: Record< string, string >;
	tip?: string;
	value: string;
}

/**
 * System status type from WooCommerce REST API.
 */
export interface WooCommerceSystemStatus {
	environment: {
		home_url: string;
		site_url: string;
		wc_version: string;
		wp_version: string;
		wp_multisite: boolean;
		wp_memory_limit: number;
		wp_debug_mode: boolean;
		wp_cron: boolean;
		language: string;
		server_info: string;
		php_version: string;
		php_max_execution_time: number;
		php_max_input_vars: number;
		curl_version: string;
		max_upload_size: number;
		default_timezone: string;
		fsockopen_or_curl_enabled: boolean;
		soapclient_enabled: boolean;
		domdocument_enabled: boolean;
		gzip_enabled: boolean;
		mbstring_enabled: boolean;
	};
	database: {
		wc_database_version: string;
		database_prefix: string;
		maxmind_geoip_database: string;
		database_tables: Record< string, unknown >;
	};
	active_plugins: Array< {
		plugin: string;
		name: string;
		version: string;
		version_latest: string;
		url: string;
		author_name: string;
		author_url: string;
		network_activated: boolean;
	} >;
	theme: {
		name: string;
		version: string;
		version_latest: string;
		author_url: string;
		is_child_theme: boolean;
		has_woocommerce_support: boolean;
		has_woocommerce_file: boolean;
		has_outdated_templates: boolean;
		overrides: Array< {
			file: string;
			parent_version: string;
			core_version: string;
		} >;
		parent_name: string;
		parent_version: string;
		parent_author_url: string;
	};
	settings: {
		api_enabled: boolean;
		force_ssl: boolean;
		currency: string;
		currency_symbol: string;
		currency_position: string;
		thousand_separator: string;
		decimal_separator: string;
		number_of_decimals: number;
		geolocation_enabled: boolean;
		taxonomies: Record< string, string >;
	};
	security: {
		secure_connection: boolean;
		hide_errors: boolean;
	};
}
