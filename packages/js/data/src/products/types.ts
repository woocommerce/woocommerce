/**
 * External dependencies
 */
import { Schema } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ProductCategory } from '../product-categories/types';
import { BaseQueryParams } from '../types';

export type ProductType = 'simple' | 'grouped' | 'external' | 'variable';
export type ProductStatus =
	| 'auto-draft'
	| 'deleted'
	| 'draft'
	| 'pending'
	| 'private'
	| 'publish'
	| 'any'
	| 'trash'
	| 'future';

export type ProductDownload = {
	id: string;
	name: string;
	file: string;
};

export type ProductAttribute = {
	id: number;
	name: string;
	position: number;
	visible: boolean;
	variation: boolean;
	options: string[];
};

export type ProductDimensions = {
	width: string;
	height: string;
	length: string;
};

export type Product< Status = ProductStatus, Type = ProductType > = Omit<
	Schema.Post,
	'status' | 'categories'
> & {
	attributes: ProductAttribute[];
	average_rating: string;
	backordered: boolean;
	backorders: 'no' | 'notify' | 'yes';
	backorders_allowed: boolean;
	button_text: string;
	categories: Pick< ProductCategory, 'id' | 'name' | 'slug' >[];
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	date_on_sale_from_gmt: string | null;
	date_on_sale_to_gmt: string | null;
	description: string;
	dimensions: ProductDimensions;
	download_expiry: number;
	download_limit: number;
	downloadable: boolean;
	downloads: ProductDownload[];
	external_url: string;
	featured: boolean;
	generated_slug: string;
	id: number;
	low_stock_amount: number;
	manage_stock: boolean;
	menu_order: number;
	name: string;
	on_sale: boolean;
	permalink: string;
	permalink_template: string;
	price: string;
	price_html: string;
	purchasable: boolean;
	regular_price: string;
	rating_count: number;
	related_ids: number[];
	reviews_allowed: boolean;
	sale_price: string;
	shipping_class: string;
	shipping_class_id: number;
	shipping_required: boolean;
	shipping_taxable: boolean;
	short_description: string;
	slug: string;
	sku: string;
	status: Status;
	stock_quantity: number;
	stock_status: 'instock' | 'outofstock' | 'onbackorder';
	tax_class: 'standard' | 'reduced-rate' | 'zero-rate' | undefined;
	tax_status: 'taxable' | 'shipping' | 'none';
	total_sales: number;
	type: Type;
	variations: number[];
	virtual: boolean;
	weight: string;
};

export const productReadOnlyProperties = [
	'average_rating',
	'backordered',
	'backorders_allowed',
	'date_created',
	'date_created_gmt',
	'date_modified',
	'date_modified_gmt',
	'generated_slug',
	'id',
	'on_sale',
	'permalink',
	'permalink_template',
	'price',
	'price_html',
	'purchasable',
	'rating_count',
	'related_ids',
	'shipping_class_id',
	'shipping_required',
	'shipping_taxable',
	'total_sales',
	'variations',
] as const;

export type ReadOnlyProperties = typeof productReadOnlyProperties[ number ];

export type PartialProduct = Partial< Product > & Pick< Product, 'id' >;

export type ProductQuery<
	Status = ProductStatus,
	Type = ProductType
> = BaseQueryParams< keyof Product > & {
	orderby:
		| 'date'
		| 'id'
		| 'include'
		| 'title'
		| 'slug'
		| 'price'
		| 'popularity'
		| 'rating';
	slug: string;
	status: Status;
	type: Type;
	sku: string;
	featured: boolean;
	category: string;
	tag: string;
	shipping_class: string;
	attribute: string;
	attribute_term: string;
	tax_class: 'standard' | 'reduced-rate' | 'zero-rate';
	on_sale: boolean;
	min_price: string;
	max_price: string;
	stock_status: 'instock' | 'outofstock' | 'onbackorder';
};
