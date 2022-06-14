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
	| 'draft'
	| 'pending'
	| 'private'
	| 'publish'
	| 'any'
	| 'future';

export type Product<
	Status = ProductStatus,
	Type = ProductType
> = Schema.Post & {
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
	price: string;
	regular_price: string;
	sale_price: string;
};

export type ReadOnlyProperties =
	| 'id'
	| 'permalink'
	| 'date_created'
	| 'date_created_gmt'
	| 'date_modified'
	| 'date_modified_gmt'
	| 'price'
	| 'price_html'
	| 'on_sale'
	| 'purchasable'
	| 'total_sales'
	| 'backorders_allowed'
	| 'backordered'
	| 'shipping_required'
	| 'shipping_taxable'
	| 'shipping_class_id'
	| 'average_rating'
	| 'rating_count'
	| 'related_ids'
	| 'variations';

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
	stock_status: 'instock' | 'outofstock';
};
