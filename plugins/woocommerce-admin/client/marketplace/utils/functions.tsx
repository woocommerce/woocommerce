/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { Product, ProductType } from '../components/product-list/types';
import {
	MARKETPLACE_HOST,
	MARKETPLACE_CATEGORY_API_PATH,
} from '../components/constants';
import { CategoryAPIItem } from '../components/category-selector/types';
import { LOCALE } from '../../utils/admin-settings';

interface ProductGroup {
	id: string;
	title: string;
	items: Product[];
	url: string;
	itemType: ProductType;
}

// Fetch data for the discover page from the WooCommerce.com API
async function fetchDiscoverPageData(): Promise< ProductGroup[] > {
	let url = '/wc/v3/marketplace/featured';

	if ( LOCALE.userLocale ) {
		url = `${ url }?locale=${ LOCALE.userLocale }`;
	}

	try {
		return await apiFetch( { path: url.toString() } );
	} catch ( error ) {
		return [];
	}
}

function fetchCategories( type: ProductType ): Promise< CategoryAPIItem[] > {
	const url = new URL( MARKETPLACE_HOST + MARKETPLACE_CATEGORY_API_PATH );

	if ( LOCALE.userLocale ) {
		url.searchParams.set( 'locale', LOCALE.userLocale );
	}

	// We don't define parent for extensions since that is provided by default
	// This is to ensure the old marketplace continues to work when this isn't defined
	if ( type === ProductType.theme ) {
		url.searchParams.set( 'parent', 'themes' );
	}

	return fetch( url.toString() )
		.then( ( response ) => {
			if ( ! response.ok ) {
				throw new Error( response.statusText );
			}

			return response.json();
		} )
		.then( ( json ) => {
			return json;
		} )
		.catch( () => {
			return [];
		} );
}

// Append UTM parameters to a URL, being aware of existing query parameters
const appendURLParams = (
	url: string,
	utmParams: Array< [ string, string ] >
): string => {
	if ( ! url ) {
		return url;
	}

	const urlObject = new URL( url );
	if ( ! urlObject ) {
		return url;
	}
	utmParams.forEach( ( [ key, value ] ) => {
		urlObject.searchParams.set( key, value );
	} );
	return urlObject.toString();
};

export {
	fetchDiscoverPageData,
	fetchCategories,
	ProductGroup,
	appendURLParams,
};
