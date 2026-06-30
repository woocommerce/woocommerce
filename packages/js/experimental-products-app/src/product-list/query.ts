/**
 * External dependencies
 */
import type { Filter, View } from '@wordpress/dataviews';
import type {
	ProductQuery,
	ProductStatus,
	ProductType,
} from '@woocommerce/data';

type ProductStockStatus = 'instock' | 'outofstock' | 'onbackorder';

export type ProductListQuery = Omit<
	ProductQuery,
	'status' | 'stock_status'
> & {
	status?: ProductStatus | ProductStatus[];
	stock_status?: ProductStockStatus | ProductStockStatus[];
	_embed?: number;
	search_name_or_sku?: string;
	exclude_status?: ProductStatus[];
	include_types?: ProductType[];
	exclude_types?: ProductType[];
	exclude_category?: number[];
	exclude_tag?: number[];
	min_stock_quantity?: string;
	max_stock_quantity?: string;
	brand?: string;
};

const SUPPORTED_STATUS_FILTER_FIELDS = [ 'status', 'product_status' ];
const DISABLED_SORT_FIELDS = [ 'name', 'price' ];

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
	const values = Array.isArray( value ) ? value : [ value ];
	return values
		.map( ( item ) => {
			if ( typeof item === 'number' ) {
				return item;
			}
			if ( typeof item === 'string' && item.trim() !== '' ) {
				return Number( item );
			}
			return Number.NaN;
		} )
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

function applyStatusFilter( query: ProductListQuery, filter: Filter ) {
	const values = getStringValues( filter.value ) as ProductStatus[];

	if ( values.length === 0 ) {
		return;
	}

	if ( filter.operator === 'isNot' || filter.operator === 'isNone' ) {
		query.exclude_status = values;
		return;
	}

	query.status = values.length === 1 ? values[ 0 ] : values;
}

function applyTypeFilter( query: ProductListQuery, filter: Filter ) {
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

function applyCategoryFilter( query: ProductListQuery, filter: Filter ) {
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

function applyTagFilter( query: ProductListQuery, filter: Filter ) {
	const values = getNumericValues( filter.value );

	if ( values.length === 0 ) {
		return;
	}

	if ( filter.operator === 'isNone' ) {
		query.exclude_tag = values;
		return;
	}

	query.tag = values.join( ',' );
}

function applyBrandFilter( query: ProductListQuery, filter: Filter ) {
	const values = getNumericValues( filter.value );

	if ( values.length === 0 ) {
		return;
	}

	query.brand = values.join( ',' );
}

function applyStockFilter( query: ProductListQuery, filter: Filter ) {
	const stockStatuses = getStringValues(
		filter.value
	) as ProductStockStatus[];

	if ( stockStatuses.length > 0 ) {
		query.stock_status =
			stockStatuses.length === 1 ? stockStatuses[ 0 ] : stockStatuses;
	}
}

function applyPriceFilter( query: ProductListQuery, filter: Filter ) {
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

function applyStockQuantityFilter( query: ProductListQuery, filter: Filter ) {
	if ( filter.operator === 'between' && Array.isArray( filter.value ) ) {
		const [ min, max ] = filter.value;
		query.min_stock_quantity = getPriceValue( min );
		query.max_stock_quantity = getPriceValue( max );
		return;
	}

	if ( filter.operator === 'isNot' ) {
		// No WC REST param for stock_quantity exclusion; intentionally
		// unsupported server-side. The operator is exposed because the user
		// explicitly asked for it in the UI.
		return;
	}

	const raw = getPriceValue( filter.value );

	if ( ! raw ) {
		return;
	}

	const numeric = Number( raw );

	if ( ! Number.isFinite( numeric ) ) {
		return;
	}

	switch ( filter.operator ) {
		case 'is':
			query.min_stock_quantity = raw;
			query.max_stock_quantity = raw;
			return;
		case 'greaterThan':
			query.min_stock_quantity = String( numeric + 1 );
			return;
		case 'greaterThanOrEqual':
			query.min_stock_quantity = raw;
			return;
		case 'lessThan':
			query.max_stock_quantity = String( numeric - 1 );
			return;
		case 'lessThanOrEqual':
			query.max_stock_quantity = raw;
	}
}

export function buildProductListQuery( view: View ): ProductListQuery {
	const sort =
		view.sort && ! DISABLED_SORT_FIELDS.includes( view.sort.field )
			? view.sort
			: undefined;
	const query: ProductListQuery = {
		_embed: 1,
		per_page: view.perPage,
		page: view.page,
		order: sort?.direction,
		orderby:
			sort?.field === 'name'
				? 'title'
				: ( sort?.field as ProductQuery[ 'orderby' ] ),
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
			case 'brands':
				applyBrandFilter( query, filter );
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
