/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import type { AttributeMetadata } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { WooCommerceBlockLocation } from '../product-template/utils';

export enum ProductCollectionUIStatesInEditor {
	COLLECTION_PICKER = 'collection_chooser',
	PRODUCT_REFERENCE_PICKER = 'product_context_picker',
	VALID_WITH_PREVIEW = 'uses_reference_preview_mode',
	VALID = 'valid',
	DELETED_PRODUCT_REFERENCE = 'deleted_product_reference',
	// Future states
	// INVALID = 'invalid',
}

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
	dimensions: ProductCollectionDimensions;
	tagName: string;
	convertedFromProducts: boolean;
	collection?: string;
	hideControls: FilterName[];
	/**
	 * Contain the list of attributes that should be included in the queryContext
	 */
	queryContextIncludes: string[];
	forcePageReload: boolean;
	filterable: boolean;
	// eslint-disable-next-line @typescript-eslint/naming-convention
	__privatePreviewState?: PreviewState;
}

export enum LayoutOptions {
	GRID = 'flex',
	STACK = 'list',
	CAROUSEL = 'carousel',
}

export enum WidthOptions {
	FILL = 'fill',
	FIXED = 'fixed',
}

export interface ProductCollectionDisplayLayout {
	type: LayoutOptions;
	columns: number;
	shrinkColumns: boolean;
}

export interface ProductCollectionDimensions {
	widthType: WidthOptions;
	fixedWidth?: string;
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
	inherit: boolean;
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
	filterable: boolean;
	productReference?: number;
	relatedBy?: RelatedBy | undefined;
}

export type RelatedBy = {
	categories: boolean;
	tags: boolean;
};

export type ProductCollectionEditComponentProps =
	BlockEditProps< ProductCollectionAttributes > & {
		name: string;
		preview?: {
			initialPreviewState?: PreviewState;
			setPreviewState?: SetPreviewState;
		};
		usesReference?: string[];
		context: {
			templateSlug: string;
		};
		tracksLocation: string;
	};

export type ProductCollectionContentProps =
	ProductCollectionEditComponentProps & {
		location: WooCommerceBlockLocation;
		isUsingReferencePreviewMode: boolean;
		openCollectionSelectionModal: () => void;
	};

export type TProductCollectionOrder = 'asc' | 'desc';
export type TProductCollectionOrderBy =
	| 'date'
	| 'title'
	| 'popularity'
	| 'price'
	| 'rating';

export type ProductCollectionSetAttributes = (
	attrs: Partial< ProductCollectionAttributes >
) => void;

export type TrackInteraction = ( filter: CoreFilterNames | string ) => void;

export type DisplayLayoutControlProps = {
	displayLayout: ProductCollectionDisplayLayout;
	setAttributes: ProductCollectionSetAttributes;
};

export type DimensionsControlProps = {
	dimensions: ProductCollectionDimensions;
	setAttributes: ProductCollectionSetAttributes;
};

export type QueryControlProps = {
	query: ProductCollectionQuery;
	trackInteraction: TrackInteraction;
	setQueryAttribute: ( attrs: Partial< ProductCollectionQuery > ) => void;
};

export enum CoreCollectionNames {
	PRODUCT_CATALOG = 'woocommerce/product-collection/product-catalog',
	BEST_SELLERS = 'woocommerce/product-collection/best-sellers',
	FEATURED = 'woocommerce/product-collection/featured',
	NEW_ARRIVALS = 'woocommerce/product-collection/new-arrivals',
	ON_SALE = 'woocommerce/product-collection/on-sale',
	TOP_RATED = 'woocommerce/product-collection/top-rated',
	HAND_PICKED = 'woocommerce/product-collection/hand-picked',
	RELATED = 'woocommerce/product-collection/related',
	UPSELLS = 'woocommerce/product-collection/upsells',
	CROSS_SELLS = 'woocommerce/product-collection/cross-sells',
	BY_CATEGORY = 'woocommerce/product-collection/by-category',
	BY_TAG = 'woocommerce/product-collection/by-tag',
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
	DEFAULT_ORDER = 'default-order',
	STOCK_STATUS = 'stock-status',
	TAXONOMY = 'taxonomy',
	PRICE_RANGE = 'price-range',
	FILTERABLE = 'filterable',
	PRODUCTS_PER_PAGE = 'products-per-page',
	MAX_PAGES_TO_SHOW = 'max-pages-to-show',
	OFFSET = 'offset',
	RELATED_BY = 'related-by',
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
