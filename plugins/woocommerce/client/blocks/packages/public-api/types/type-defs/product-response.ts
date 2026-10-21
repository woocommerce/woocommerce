/**
 * Internal dependencies
 */
import type { CurrencyResponse } from './currency';

export interface ProductResponseItemPrices extends CurrencyResponse {
	price: string;
	regular_price: string;
	sale_price: string;
	price_range: null | { min_amount: string; max_amount: string };
}

export interface ProductResponseItemBaseData {
	value: string;
	display?: string;
	/**
	 * Machine-readable name for the entry, never translated, so it is safe to
	 * match on. Entity-encoded like every other value here, so a bare `&`
	 * arrives as `&amp;`. Added in WooCommerce 11.2.0.
	 */
	raw_key?: string;
	/**
	 * Truthy marks the entry hidden. Always a string: the Store API runs every
	 * `item_data` value through `wp_kses_post()`, which string-coerces, so a
	 * boolean set by an extension arrives as `"1"` or `""`. Test it for
	 * truthiness — comparing against `true` or `1` can never match.
	 */
	hidden?: string;
	className?: string;
}

export type ProductResponseItemData = ProductResponseItemBaseData &
	( { key: string; name?: never } | { key?: never; name: string } );

export interface ProductResponseImageItem {
	id: number;
	src: string;
	thumbnail: string;
	srcset: string;
	sizes: string;
	name: string;
	alt: string;
}

export interface ProductResponseTermItem {
	default?: boolean;
	id: number;
	name: string;
	slug: string;
	link?: string;
}

export interface ProductResponseAttributeItem {
	id: number;
	name: string;
	taxonomy: string;
	has_variations: boolean;
	terms: Array< ProductResponseTermItem >;
}

export interface ProductResponseVariationsItem {
	id: number;
	attributes: Array< ProductResponseVariationAttributeItem >;
}

export interface ProductResponseVariationAttributeItem {
	name: string;
	value: string;
}

export interface ProductResponseItem {
	id: number;
	name: string;
	parent: number;
	type: string;
	variation: string;
	permalink: string;
	sku: string;
	short_description: string;
	description: string;
	on_sale: boolean;
	prices: ProductResponseItemPrices;
	price_html: string;
	average_rating: string;
	review_count: number;
	images: Array< ProductResponseImageItem >;
	categories: Array< ProductResponseTermItem >;
	tags: Array< ProductResponseTermItem >;
	attributes: Array< ProductResponseAttributeItem >;
	variations: Array< ProductResponseVariationsItem >;
	has_options: boolean;
	is_purchasable: boolean;
	is_in_stock: boolean;
	is_on_backorder: boolean;
	low_stock_remaining: null | number;
	stock_availability: {
		text: string;
		class: string;
	};
	sold_individually: boolean;
	weight: string;
	dimensions: {
		length: string;
		width: string;
		height: string;
	};
	formatted_weight: string;
	formatted_dimensions: string;
	add_to_cart: {
		text: string;
		description: string;
		url: string;
		minimum: number;
		maximum: number;
		multiple_of: number;
		single_text: string;
	};
	slug: string;
	grouped_products: Array< number >;
	price: string;
	regular_price: string;
	sale_price: string;
}
