/**
 * Internal dependencies
 */
import { Product } from '../components/product-list/types';
import { MARKETPLACE_URL } from '../components/constants';
import { CategoryAPIItem } from '../components/category-selector/types';

interface ProductGroup {
	id: number;
	title: string;
	items: Product[];
	url: string;
}

// Fetch data for the discover page from the WooCommerce.com API
const fetchDiscoverPageData = async (): Promise< Array< ProductGroup > > => {
	const fetchUrl = MARKETPLACE_URL + '/wp-json/wccom-extensions/2.0/featured';

	return fetch( fetchUrl )
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
};

function fetchCategories(): Promise< CategoryAPIItem[] > {
	return fetch( MARKETPLACE_URL + '/wp-json/wccom-extensions/1.0/categories' )
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
