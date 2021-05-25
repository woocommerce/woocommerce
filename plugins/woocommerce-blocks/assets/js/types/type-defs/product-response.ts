/* eslint-disable camelcase -- API responses have camelcase properties */

/**
 * Internal dependencies
 */
import { CurrencyResponse } from './currency';

export interface ProductResponseItemPrices extends CurrencyResponse {
	price: string;
	regular_price: string;
	sale_price: string;
	price_range: null | { min_amount: string; max_amount: string };
	raw_prices: {
		precision: number;
		price: string;
		regular_price: string;
		sale_price: string;
	};
}

export interface ProductResponseItemData {
	name: string;
	value: string;
	display?: string;
	hidden?: boolean;
}

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
	average_rating: number;
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
	sold_individually: boolean;
	quantity_limit: number;
	add_to_cart: {
		text: string;
		description: string;
		url: string;
	};
}
