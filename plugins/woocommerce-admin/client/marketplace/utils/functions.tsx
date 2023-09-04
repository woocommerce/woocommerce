/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { Product } from '../components/product-list/types';
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

function fetchCategories(): Promise< CategoryAPIItem[] > {
	let url = MARKETPLACE_HOST + MARKETPLACE_CATEGORY_API_PATH;

	if ( LOCALE.userLocale ) {
		url = `${ url }?locale=${ LOCALE.userLocale }`;
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
const appendUTMParams = (
	url: string,
	utmParams: Array< [ string, string ] >
): string => {
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
	appendUTMParams,
};
