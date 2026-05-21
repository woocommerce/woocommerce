/**
 * External dependencies
 */
import { addQueryArgs, getQueryArgs } from '@wordpress/url';
import { Filter, View } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord, ProductStatus } from '../fields/types';
import { PRODUCT_LIST_TAB_VALUES, type StatusTab } from './constants';

export function getProductListNavigationPath(
	path: string,
	params: Record< string, string | undefined >
) {
	const [ pathname = '/' ] = path.split( '?' );
	const query = {
		...getQueryArgs( path ),
		...params,
	};
	const sanitizedQuery = Object.fromEntries(
		Object.entries( query ).filter(
			( [ key, value ] ) =>
				key !== 'undefined' && typeof value !== 'undefined'
		)
	);

	return addQueryArgs( pathname, sanitizedQuery );
}

export function getItemId( item: ProductEntityRecord ) {
	return item.id.toString();
}

export function getProductEditPostId( item: ProductEntityRecord ) {
	return item.parent_id && item.parent_id > 0 ? item.parent_id : item.id;
}

export function getProductsWithEmbeddedVariations(
	items: ProductEntityRecord[]
): ProductEntityRecord[] {
	const itemsById = new Map( items.map( ( item ) => [ item.id, item ] ) );
	const productsWithVariations = new Map< number, ProductEntityRecord >();

	function addItem( item: ProductEntityRecord ) {
		if ( productsWithVariations.has( item.id ) ) {
			return;
		}

		productsWithVariations.set( item.id, item );
	}

	items.forEach( ( item ) => {
		if ( item.parent_id && itemsById.has( item.parent_id ) ) {
			return;
		}

		addItem( item );

		item._embedded?.variations?.forEach( ( variation ) => {
			addItem( itemsById.get( variation.id ) ?? variation );
		} );
	} );

	return Array.from( productsWithVariations.values() );
}

function isProductListTabValue( value: string ): value is StatusTab {
	return PRODUCT_LIST_TAB_VALUES.includes( value as StatusTab );
}

export function getProductListTab( value?: string ): StatusTab {
	if ( value && isProductListTabValue( value ) ) {
		return value;
	}

	return 'all';
}

export function getStatusForProductListTab(
	tab: StatusTab
): ProductStatus | undefined {
	switch ( tab ) {
		case 'publish':
		case 'draft':
		case 'pending':
		case 'trash':
			return tab;
		default:
			return undefined;
	}
}

export function getSelectionFromPostId( postId?: string ) {
	return postId?.split( ',' ).filter( Boolean ) ?? [];
}

export function isProductEditorAccessible( item: ProductEntityRecord ) {
	return item.status !== 'trash';
}

function hasFilterValue( value: Filter[ 'value' ] ): boolean {
	if ( Array.isArray( value ) ) {
		return value.some( hasFilterValue );
	}

	if ( typeof value === 'string' ) {
		return value.trim() !== '';
	}

	return value !== undefined && value !== null;
}

export function hasActiveProductListSearchOrFilters( view: View ) {
	return (
		( typeof view.search === 'string' && view.search.trim() !== '' ) ||
		view.filters?.some( ( filter ) => hasFilterValue( filter.value ) ) ===
			true
	);
}
