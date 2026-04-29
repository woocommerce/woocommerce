/**
 * External dependencies
 */
import type { Filter, View } from '@wordpress/dataviews';
import type {
	ProductQuery,
	ProductStatus,
	ProductType,
} from '@woocommerce/data';

export type ProductListQuery = Omit< ProductQuery, 'status' > & {
	status?: ProductStatus | ProductStatus[];
	search_name_or_sku?: string;
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
	const values = Array.isArray( value ) ? value : [ value ];
	return values.map( ( item ) => {
		if ( typeof item === 'number' ) {
			return item;
		}
		if ( typeof item === 'string' ) {
			return Number( item );
		}
		return Number.NaN;
	} );
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

function applyStockFilter( query: ProductListQuery, filter: Filter ) {
	const [ stockStatus ] = getStringValues( filter.value );

	if ( stockStatus ) {
		query.stock_status = stockStatus as ProductListQuery[ 'stock_status' ];
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
			case 'stock':
				applyStockFilter( query, filter );
				break;
			case 'price':
				applyPriceFilter( query, filter );
				break;
		}
	} );

	return query;
}
