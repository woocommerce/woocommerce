/**
 * Product image data structure
 */
export interface ProductEntityResponseImage {
	id: number;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	src: string;
	name: string;
	alt: string;
	srcset: string;
	sizes: string;
	thumbnail: string;
}

interface ProductEntityResponseBase {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	type: 'simple' | 'grouped' | 'external' | 'variable' | 'variation';
	status: 'draft' | 'pending' | 'private' | 'publish' | 'trash';
	featured: boolean;
	catalog_visibility: 'visible' | 'catalog' | 'search' | 'hidden';
	description: string;
	short_description: string;
	sku: string;
	price: string;
	regular_price: string;
	sale_price: string;
	rating_count: number;
	average_rating: string;
	stock_status: 'instock' | 'outofstock' | 'onbackorder';
	/**
	 * Experimental price fields for grouped products
	 */
	min_price?: string;
	max_price?: string;
	add_to_cart?: {
		url: string;
		description: string;
		text: string;
		single_text: string;
	};
}

/**
 * Grouped product specific type
 */
export interface GroupedProductResponse extends ProductEntityResponseBase {
	type: 'grouped';
	grouped_products: number[];
}

/**
 * Variable product specific type
 */
export interface VariableProductResponse extends ProductEntityResponseBase {
	type: 'variable';
}

/**
 * Simple product specific type
 */
export interface SimpleProductResponse extends ProductEntityResponseBase {
	type: 'simple';
}

/**
 * External product specific type
 */
export interface ExternalProductResponse extends ProductEntityResponseBase {
	type: 'external';
	button_text: string;
}

export type ProductEntityResponse =
	| SimpleProductResponse
	| GroupedProductResponse
	| VariableProductResponse
	| ExternalProductResponse;
