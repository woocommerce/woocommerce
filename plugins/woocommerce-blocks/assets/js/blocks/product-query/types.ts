/**
 * External dependencies
 */
import type { EditorBlock } from '@woocommerce/types';

export interface ProductQueryArguments {
	/**
	 * Display only products on sale.
	 *
	 * Will generate the following `meta_query`:
	 *
	 * ```
	 * array(
	 *   'relation' => 'OR',
	 *   array( // Simple products type
	 *     'key'     => '_sale_price',
	 *     'value'   => 0,
	 *     'compare' => '>',
	 *     'type'    => 'numeric',
	 *   ),
	 *   array( // Variable products type
	 *     'key'     => '_min_variation_sale_price',
	 *     'value'   => 0,
	 *     'compare' => '>',
	 *     'type'    => 'numeric',
	 *   ),
	 * )
	 * ```
	 */
	// Disabling naming convention because we are namespacing our
	// custom attributes inside a core block. Prefixing with underscores
	// will help signify our intentions.
	// eslint-disable-next-line @typescript-eslint/naming-convention
	__woocommerceOnSale?: boolean;
}

export type ProductQueryBlock = EditorBlock< QueryBlockAttributes >;

export interface ProductQueryAttributes {
	/**
	 * An array of controls to disable in the inspector.
	 *
	 * @example  `[ 'stockStatus' ]`  will not render the dropdown for stock status.
	 */
	disabledInspectorControls?: string[];
	/**
	 * Query attributes that define which products will be fetched.
	 */
	query?: ProductQueryArguments;
}

export interface QueryBlockAttributes {
	allowControls?: string[];
	displayLayout?: {
		type: 'flex' | 'list';
		columns?: number;
	};
	namespace?: string;
	query: QueryBlockQuery & ProductQueryArguments;
}

export interface QueryBlockQuery {
	author?: string;
	exclude?: string[];
	inherit: boolean;
	offset?: number;
	order: 'asc' | 'desc';
	orderBy: 'date' | 'relevance';
	pages?: number;
	parents?: number[];
	perPage?: number;
	postType: string;
	search?: string;
	sticky?: string;
	taxQuery?: string;
}

export enum QueryVariation {
	/** The main, fully customizable, Product Query block */
	PRODUCT_QUERY = 'woocommerce/product-query',
	/** Only shows products on sale */
	PRODUCTS_ON_SALE = 'woocommerce/query-products-on-sale',
}
