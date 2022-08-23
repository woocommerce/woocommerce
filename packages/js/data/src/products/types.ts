/**
 * External dependencies
 */
import { Schema } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { BaseQueryParams } from '../types';

export type ProductType = 'simple' | 'grouped' | 'external' | 'variable';
export type ProductStatus =
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

export type Product< Status = ProductStatus, Type = ProductType > = Omit<
	Schema.Post,
	'status'
> & {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	type: Type;
	status: Status;
	featured: boolean;
	description: string;
	short_description: string;
	sku: string;
	date_on_sale_from: string | null;
	date_on_sale_from_gmt: string | null;
	date_on_sale_to: string | null;
	date_on_sale_to_gmt: string | null;
	virtual: boolean;
	downloadable: boolean;
	downloads: ProductDownload[];
	download_limit: number;
	download_expiry: number;
	external_url: string;
	button_text: string;
	tax_status: 'taxable' | 'shipping' | 'none';
	tax_class: 'standard' | 'reduced-rate' | 'zero-rate' | undefined;
	manage_stock: boolean;
	stock_quantity: number;
	stock_status: 'instock' | 'outofstock' | 'onbackorder';
	backorders: 'no' | 'notify' | 'yes';
	price: string;
	price_html: string;
	regular_price: string;
	sale_price: string;
	on_sale: boolean;
	purchasable: boolean;
	total_sales: number;
	backorders_allowed: boolean;
	backordered: boolean;
	shipping_required: boolean;
	shipping_taxable: boolean;
	shipping_class_id: number;
	average_rating: string;
	rating_count: number;
	related_ids: number[];
	variations: number[];
};

export const productReadOnlyProperties = [
	'id',
	'permalink',
	'date_created',
	'date_created_gmt',
	'date_modified',
	'date_modified_gmt',
	'price',
	'price_html',
	'on_sale',
	'purchasable',
	'total_sales',
	'backorders_allowed',
	'backordered',
	'shipping_required',
	'shipping_taxable',
	'shipping_class_id',
	'average_rating',
	'rating_count',
	'related_ids',
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
