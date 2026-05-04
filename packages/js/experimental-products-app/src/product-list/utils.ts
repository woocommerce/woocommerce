/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord, ProductStatus } from '../fields/types';
import { PRODUCT_LIST_TAB_VALUES, type StatusTab } from './constants';

export function getProductListNavigationPath(
	path: string,
	params: Record< string, string >
) {
	const [ pathname = '/' ] = path.split( '?' );

	return addQueryArgs( pathname, params );
}

export function getItemId( item: ProductEntityRecord ) {
	return item.id.toString();
}

export function getProductListItemLevel( item: ProductEntityRecord ) {
	return item.parent_id && item.parent_id > 0 ? 1 : 0;
}

export function sortProductsForHierarchy(
	items: ProductEntityRecord[]
): ProductEntityRecord[] {
	const itemIds = new Set( items.map( ( item ) => item.id ) );
	const childItemsByParentId = new Map< number, ProductEntityRecord[] >();

	items.forEach( ( item ) => {
		if ( item.parent_id && itemIds.has( item.parent_id ) ) {
			const childItems = childItemsByParentId.get( item.parent_id ) ?? [];

			childItemsByParentId.set( item.parent_id, [ ...childItems, item ] );
		}
	} );

	return items.reduce< ProductEntityRecord[] >( ( sortedItems, item ) => {
		if ( item.parent_id && itemIds.has( item.parent_id ) ) {
			return sortedItems;
		}

		sortedItems.push( item );

		const childItems = childItemsByParentId.get( item.id );

		if ( childItems ) {
			sortedItems.push( ...childItems );
		}

		return sortedItems;
	}, [] );
}

export function getProductsWithEmbeddedVariations(
	items: ProductEntityRecord[]
): ProductEntityRecord[] {
	const itemsById = new Map( items.map( ( item ) => [ item.id, item ] ) );
	const addedIds = new Set< number >();
	const productsWithVariations: ProductEntityRecord[] = [];

	function addItem( item: ProductEntityRecord ) {
		if ( addedIds.has( item.id ) ) {
			return;
		}

		addedIds.add( item.id );
		productsWithVariations.push( item );
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

	items.forEach( addItem );

	return productsWithVariations;
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
