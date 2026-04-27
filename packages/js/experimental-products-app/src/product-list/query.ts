/**
 * External dependencies
 */
import type { View } from '@wordpress/dataviews';
import type {
	ProductQuery,
	ProductStatus,
	ProductType,
} from '@woocommerce/data';

type ExtendedOperator =
	| NonNullable< View[ 'filters' ] >[ number ][ 'operator' ]
	| 'between'
	| 'greaterThanOrEqual'
	| 'lessThanOrEqual';

type ProductListFilter = Omit<
	NonNullable< View[ 'filters' ] >[ number ],
	'operator'
> & {
	operator: ExtendedOperator;
};

export type ProductListQuery = ProductQuery & {
	search_name_or_sku?: string;
	include_status?: ProductStatus[];
	exclude_status?: ProductStatus[];
	include_types?: ProductType[];
	exclude_types?: ProductType[];
	exclude_category?: number[];
	min_stock_quantity?: string;
	max_stock_quantity?: string;
};

const SUPPORTED_STATUS_FILTER_FIELDS = [ 'status', 'product_status' ];

function isStringArray( value: unknown ): value is string[] {
	return (
		Array.isArray( value ) &&
		value.every( ( item ) => typeof item === 'string' )
	);
}

function getStringValues( value: unknown ): string[] {
	if ( isStringArray( value ) ) {
		return value.filter( Boolean );
	}

	if ( typeof value === 'string' && value ) {
		return [ value ];
	}

	return [];
}

function getNumericValues( value: unknown ): number[] {
	return getStringValues( value )
		.map( ( item ) => Number( item ) )
		.filter( Number.isFinite );
}

function getPriceValue( value: unknown ): string | undefined {
	if ( typeof value === 'number' && Number.isFinite( value ) ) {
		return String( value );
	}

	if ( typeof value === 'string' && value !== '' ) {
		return value;
	}

	return undefined;
}

function getAttributeFilters( value: unknown ) {
	return getStringValues( value )
		.map( ( item ) => {
			const [ taxonomy, termId ] = item.split( ':' );
			const parsedTermId = Number( termId );

			if ( ! taxonomy || ! Number.isFinite( parsedTermId ) ) {
				return null;
			}

			return {
				taxonomy,
				termId: parsedTermId,
			};
		} )
		.filter(
			(
				item
			): item is {
				taxonomy: string;
				termId: number;
			} => item !== null
		);
}

function applyStatusFilter(
	query: ProductListQuery,
	filter: ProductListFilter
) {
	const values = getStringValues( filter.value ) as ProductStatus[];

	if ( values.length === 0 ) {
		return;
	}

	if ( filter.operator === 'isNot' || filter.operator === 'isNone' ) {
		query.exclude_status = values;
		return;
	}

	query.include_status = values;
}

function applyTypeFilter( query: ProductListQuery, filter: ProductListFilter ) {
	const values = getStringValues( filter.value ) as ProductType[];

	if ( values.length === 0 ) {
		return;
	}

	if ( filter.operator === 'isNot' || filter.operator === 'isNone' ) {
		query.exclude_types = values;
		return;
	}

	query.include_types = values;
}

function applyCategoryFilter(
	query: ProductListQuery,
	filter: ProductListFilter
) {
	const values = getNumericValues( filter.value );

	if ( values.length === 0 ) {
		return;
	}

	if ( filter.operator === 'isNone' ) {
		query.exclude_category = values;
		return;
	}

	query.category = values.join( ',' );
}

function applyTagFilter( query: ProductListQuery, filter: ProductListFilter ) {
	const values = getNumericValues( filter.value );

	if ( values.length > 0 ) {
		query.tag = values.join( ',' );
	}
}

function applyShippingClassFilter(
	query: ProductListQuery,
	filter: ProductListFilter
) {
	const values = getNumericValues( filter.value );

	if ( values.length > 0 ) {
		query.shipping_class = values.join( ',' );
	}
}

function applyStockFilter(
	query: ProductListQuery,
	filter: ProductListFilter
) {
	const [ stockStatus ] = getStringValues( filter.value );

	if ( stockStatus ) {
		query.stock_status = stockStatus as ProductListQuery[ 'stock_status' ];
	}
}

function applyAttributeFilter(
	query: ProductListQuery,
	filter: ProductListFilter
) {
	const attributes = getAttributeFilters( filter.value );

	if ( attributes.length === 0 ) {
		return;
	}

	const [ firstAttribute ] = attributes;

	if ( ! firstAttribute ) {
		return;
	}

	const matchingAttributes = attributes.filter(
		( attribute ) => attribute.taxonomy === firstAttribute.taxonomy
	);

	query.attribute = firstAttribute.taxonomy;
	query.attribute_term = matchingAttributes
		.map( ( attribute ) => attribute.termId )
		.join( ',' );
}

function applyPriceFilter(
	query: ProductListQuery,
	filter: ProductListFilter
) {
	if ( filter.operator === 'between' && Array.isArray( filter.value ) ) {
		const [ minPrice, maxPrice ] = filter.value;
		query.min_price = getPriceValue( minPrice );
		query.max_price = getPriceValue( maxPrice );
		return;
	}

	const price = getPriceValue( filter.value );

	if ( ! price ) {
		return;
	}

	if ( filter.operator === 'greaterThanOrEqual' ) {
		query.min_price = price;
		return;
	}

	if ( filter.operator === 'lessThanOrEqual' ) {
		query.max_price = price;
		return;
	}

	query.min_price = price;
	query.max_price = price;
}

function applyStockQuantityFilter(
	query: ProductListQuery,
	filter: ProductListFilter
) {
	if ( filter.operator === 'between' && Array.isArray( filter.value ) ) {
		const [ minStockQuantity, maxStockQuantity ] = filter.value;
		query.min_stock_quantity = getPriceValue( minStockQuantity );
		query.max_stock_quantity = getPriceValue( maxStockQuantity );
		return;
	}

	const stockQuantity = getPriceValue( filter.value );

	if ( ! stockQuantity ) {
		return;
	}

	if ( filter.operator === 'greaterThanOrEqual' ) {
		query.min_stock_quantity = stockQuantity;
		return;
	}

	if ( filter.operator === 'lessThanOrEqual' ) {
		query.max_stock_quantity = stockQuantity;
		return;
	}

	query.min_stock_quantity = stockQuantity;
	query.max_stock_quantity = stockQuantity;
}

export function buildProductListQuery( view: View ): ProductListQuery {
	const query: ProductListQuery = {
		per_page: view.perPage,
		page: view.page,
		order: view.sort?.direction,
		orderby:
			view.sort?.field === 'name'
				? 'title'
				: ( view.sort?.field as ProductQuery[ 'orderby' ] ),
		search_name_or_sku: view.search || undefined,
	};

	view.filters?.forEach( ( filter ) => {
		if ( SUPPORTED_STATUS_FILTER_FIELDS.includes( filter.field ) ) {
			applyStatusFilter( query, filter );
			return;
		}

		switch ( filter.field ) {
			case 'type':
				applyTypeFilter( query, filter );
				break;
			case 'categories':
				applyCategoryFilter( query, filter );
				break;
			case 'tags':
				applyTagFilter( query, filter );
				break;
			case 'shipping_class':
				applyShippingClassFilter( query, filter );
				break;
			case 'attributes':
				applyAttributeFilter( query, filter );
				break;
			case 'stock':
				applyStockFilter( query, filter );
				break;
			case 'price':
				applyPriceFilter( query, filter );
				break;
			case 'stock_quantity':
				applyStockQuantityFilter( query, filter );
				break;
		}
	} );

	return query;
}
