/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import { type AttributeMetadata } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { WooCommerceBlockLocation } from '../product-template/utils';

export interface ProductCollectionAttributes {
	query: ProductCollectionQuery;
	queryId: number;
	queryContext: [
		{
			page: number;
		}
	];
	templateSlug: string;
	displayLayout: ProductCollectionDisplayLayout;
	tagName: string;
	convertedFromProducts: boolean;
	collection?: string;
	hideControls: FilterName[];
	/**
	 * Contain the list of attributes that should be included in the queryContext
	 */
	queryContextIncludes: string[];
	forcePageReload: boolean;
	// eslint-disable-next-line @typescript-eslint/naming-convention
	__privatePreviewState?: PreviewState;
}

export enum LayoutOptions {
	GRID = 'flex',
	STACK = 'list',
}

export interface ProductCollectionDisplayLayout {
	type: LayoutOptions;
	columns: number;
	shrinkColumns: boolean;
}

export enum ETimeFrameOperator {
	IN = 'in',
	NOT_IN = 'not-in',
}

export interface TimeFrame {
	operator?: ETimeFrameOperator;
	value?: string;
}

export interface PriceRange {
	min?: number | undefined;
	max?: number | undefined;
}

export interface ProductCollectionQuery {
	exclude: string[];
	inherit: boolean | null;
	offset: number;
	order: TProductCollectionOrder;
	orderBy: TProductCollectionOrderBy;
	pages: number;
	perPage: number;
	postType: string;
	search: string;
	taxQuery: Record< string, number[] >;
	/**
	 * If true, show only featured products.
	 */
	featured: boolean;
	timeFrame: TimeFrame | undefined;
	woocommerceOnSale: boolean;
	/**
	 * Filter products by their stock status.
	 *
	 * Will generate the following `meta_query`:
	 *
	 * ```
	 * array(
	 *   'key'     => '_stock_status',
	 *   'value'   => (array) $stock_statuses,
	 *   'compare' => 'IN',
	 * ),
	 * ```
	 */
	woocommerceStockStatus: string[];
	woocommerceAttributes: AttributeMetadata[];
	isProductCollectionBlock: boolean;
	woocommerceHandPickedProducts: string[];
	priceRange: undefined | PriceRange;
}

export type ProductCollectionEditComponentProps =
	BlockEditProps< ProductCollectionAttributes > & {
		openCollectionSelectionModal: () => void;
		preview: {
			initialPreviewState?: PreviewState;
			setPreviewState?: SetPreviewState;
		};
		context: {
			templateSlug: string;
		};
	};

export type TProductCollectionOrder = 'asc' | 'desc';
export type TProductCollectionOrderBy =
	| 'date'
	| 'title'
	| 'popularity'
	| 'rating';

export type ProductCollectionSetAttributes = (
	attrs: Partial< ProductCollectionAttributes >
) => void;

export type DisplayLayoutControlProps = {
	displayLayout: ProductCollectionDisplayLayout;
	setAttributes: ProductCollectionSetAttributes;
};
export type QueryControlProps = {
	query: ProductCollectionQuery;
	setQueryAttribute: ( attrs: Partial< ProductCollectionQuery > ) => void;
};

export enum CoreCollectionNames {
	PRODUCT_CATALOG = 'woocommerce/product-collection/product-catalog',
	CUSTOM = 'woocommerce/product-collection/custom',
	BEST_SELLERS = 'woocommerce/product-collection/best-sellers',
	FEATURED = 'woocommerce/product-collection/featured',
	NEW_ARRIVALS = 'woocommerce/product-collection/new-arrivals',
	ON_SALE = 'woocommerce/product-collection/on-sale',
	TOP_RATED = 'woocommerce/product-collection/top-rated',
}

export enum CoreFilterNames {
	ATTRIBUTES = 'attributes',
	CREATED = 'created',
	FEATURED = 'featured',
	HAND_PICKED = 'hand-picked',
	INHERIT = 'inherit',
	KEYWORD = 'keyword',
	ON_SALE = 'on-sale',
	ORDER = 'order',
	STOCK_STATUS = 'stock-status',
	TAXONOMY = 'taxonomy',
}

export type CollectionName = CoreCollectionNames | string;
export type FilterName = CoreFilterNames | string;

export interface PreviewState {
	isPreview: boolean;
	previewMessage: string;
}

export type SetPreviewState = ( args: {
	setState: ( previewState: PreviewState ) => void;
	location: WooCommerceBlockLocation;
	attributes: ProductCollectionAttributes;
} ) => void | ( () => void );
