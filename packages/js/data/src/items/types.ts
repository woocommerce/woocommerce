/**
 * Internal dependencies
 */
import { BaseQueryParams } from '../types/query-params';

type Link = {
	href: string;
};

// Category, Product, Customer item id is a number, and leaderboards item is a string.
export type ItemID = number | string;

export type ItemType = 'categories' | 'products' | 'customers' | 'leaderboards';

export type ItemImage = {
	id: number;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	src: string;
	name: string;
	alt: string;
};

// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/rest-api/Controllers/Version3/class-wc-rest-product-categories-controller.php#L97-L208
export type CategoryItem = {
	id: number;
	name: string;
	slug: string;
	parent: number;
	description: string;
	display: string;
	image: null | ItemImage;
	menu_order: number;
	count: number;
	_links: {
		collection: Array< Link >;
		self: Array< Link >;
	};
};

// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/API/Products.php#L72-L83
// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/rest-api/Controllers/Version3/class-wc-rest-products-controller.php#L809-L1423
export type ProductItem = {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	attributes: Array< {
		id: number;
		name: string;
		position: number;
		visible: boolean;
		variation: boolean;
		options: string[];
	} >;
	average_rating: string;
	backordered: boolean;
	backorders: string;
	backorders_allowed: boolean;
	button_text: string;
	catalog_visibility: string;
	categories: Array< {
		id: number;
		name: string;
		slug: string;
	} >;
	cross_sell_ids: number[];
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	date_on_sale_from: null | string;
	date_on_sale_from_gmt: null | string;
	date_on_sale_to: null | string;
	date_on_sale_to_gmt: null | string;
	default_attributes: Array< {
		id: number;
		name: string;
		option: string;
	} >;
	description: string;
	dimensions: { length: string; width: string; height: string };
	download_expiry: number;
	download_limit: number;
	downloadable: boolean;
	downloads: Array< {
		id: number;
		name: string;
		file: string;
	} >;
	external_url: string;
	featured: boolean;
	grouped_products: Array< number >;
	has_options: boolean;
	images: Array< ItemImage >;
	low_stock_amount: null | number;
	manage_stock: boolean;
	menu_order: number;
	meta_data: Array< {
		id: number;
		key: string;
		value: string;
	} >;
	on_sale: boolean;
	parent_id: number;
	price: string;
	price_html: string;
	purchasable: boolean;
	purchase_note: string;
	rating_count: number;
	regular_price: string;
	related_ids: number[];
	reviews_allowed: boolean;
	sale_price: string;
	shipping_class: string;
	shipping_class_id: number;
	shipping_required: boolean;
	shipping_taxable: boolean;
	short_description: string;
	sku: string;
	sold_individually: boolean;
	status: string;
	stock_quantity: number;
	stock_status: string;
	tags: Array< {
		id: number;
		name: string;
		slug: string;
	} >;
	tax_class: string;
	tax_status: string;
	total_sales: number;
	type: string;
	upsell_ids: number[];
	variations: Array< {
		id: number;
		date_created: string;
		date_created_gmt: string;
		date_modified: string;
		date_modified_gmt: string;
		attributes: Array< {
			id: number;
			name: string;
			option: string;
		} >;
		image: string;
		price: string;
		regular_price: string;
		sale_price: string;
		sku: string;
		stock_quantity: number;
		tax_class: string;
		tax_status: string;
		total_sales: number;
		weight: string;
	} >;
	virtual: boolean;
	weight: string;
	last_order_date: string;
};

// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/API/Reports/Customers/Controller.php#L221-L318
export type CustomerItem = {
	id: number;
	user_id: number;
	name: string;
	username: string;
	country: string;
	city: string;
	state: string;
	postcode: string;
	date_registered: string;
	date_registered_gmt: string;
	date_last_active: string;
	date_last_active_gmt: string;
	orders_count: number;
	total_spent: number;
	avg_order_value: number;
	_links: {
		self: Array< Link >;
	};
};

// https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/API/Leaderboards.php#L527-L585
export type LeaderboardItem = {
	id: string;
	label: string;
	headers: {
		label: string;
	};
	rows: {
		display: string;
		value: string;
	};
};

export type Item = Partial<
	CategoryItem | ProductItem | CustomerItem | LeaderboardItem
> & {
	id: ItemID;
};

export type ItemInfer< T > = Partial<
	T extends 'categories'
		? CategoryItem
		: T extends 'products'
		? ProductItem
		: T extends 'customers'
		? CustomerItem
		: T extends 'leaderboards'
		? LeaderboardItem
		: never
> & {
	id: ItemID;
};

export type ItemsState = {
	items:
		| Record< string, { data: ItemID[] } | number >
		| Record< string, never >;
	data: Partial< Record< ItemType, Record< ItemID, Item > > >;
	errors: {
		[ key: string ]: unknown;
	};
};

export type Query = Partial< BaseQueryParams >;
