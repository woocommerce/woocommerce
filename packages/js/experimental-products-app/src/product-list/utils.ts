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
