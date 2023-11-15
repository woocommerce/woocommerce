/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	Product,
	ProductType,
	SearchAPIProductType,
	SearchAPIJSONType,
} from '../components/product-list/types';
import {
	MARKETPLACE_HOST,
	MARKETPLACE_CATEGORY_API_PATH,
	MARKETPLACE_SEARCH_API_PATH,
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

// The fetchCache stores the results of GET fetch/apiFetch calls from the Marketplace, in RAM, for performance
const maxFetchCacheSize = 100;
const fetchCache = new Map();

function maybePruneFetchCache() {
	while ( fetchCache.size > maxFetchCacheSize ) {
		fetchCache.delete( fetchCache.keys().next().value );
	}
}

// Wrapper around apiFetch() that caches results in memory
async function apiFetchWithCache( params: object ): Promise< object > {
	// Attempt to fetch from cache:
	const cacheKey = JSON.stringify( params );
	if ( fetchCache.get( cacheKey ) ) {
		return new Promise( ( resolve ) => {
			resolve( fetchCache.get( cacheKey ) );
		} );
	}

	// Failing that, fetch using apiCache:
	return new Promise( ( resolve, reject ) => {
		apiFetch( params )
			.then( ( json ) => {
				fetchCache.set( cacheKey, json );
				maybePruneFetchCache();
				resolve( json as object );
			} )
			.catch( () => {
				reject();
			} );
	} );
}

// Wrapper around fetch() that caches results in memory
async function fetchJsonWithCache(
	url: string,
	abortSignal?: AbortSignal
): Promise< object > {
	// Attempt to fetch from cache:
	if ( fetchCache.get( url ) ) {
		return new Promise( ( resolve ) => {
			resolve( fetchCache.get( url ) );
		} );
	}

	// Failing that, fetch from net:
	return new Promise( ( resolve, reject ) => {
		fetch( url, { signal: abortSignal } )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( response.statusText );
				}
				return response.json();
			} )
			.then( ( json ) => {
				fetchCache.set( url, json );
				maybePruneFetchCache();
				resolve( json );
			} )
			.catch( () => {
				reject();
			} );
	} );
}

// Fetch search results for a given set of URLSearchParams from the Woo.com API
async function fetchSearchResults(
	params: URLSearchParams,
	abortSignal?: AbortSignal
): Promise< Product[] > {
	const url =
		MARKETPLACE_HOST +
		MARKETPLACE_SEARCH_API_PATH +
		'?' +
		params.toString();

	// Fetch data from WCCOM API
	return new Promise( ( resolve, reject ) => {
		fetchJsonWithCache( url, abortSignal )
			.then( ( json ) => {
				/**
				 * Product card component expects a Product type.
				 * So we build that object from the API response.
				 */
				const products = ( json as SearchAPIJSONType ).products.map(
					( product: SearchAPIProductType ): Product => {
						return {
							id: product.id,
							title: product.title,
							image: product.image,
							type: product.type,
							description: product.excerpt,
							vendorName: product.vendor_name,
							vendorUrl: product.vendor_url,
							icon: product.icon,
							url: product.link,
							// Due to backwards compatibility, raw_price is from search API, price is from featured API
							price: product.raw_price ?? product.price,
							averageRating: product.rating ?? 0,
							reviewsCount: product.reviews_count ?? 0,
						};
					}
				);
				resolve( products );
			} )
			.catch( () => reject );
	} );
}

// Fetch data for the discover page from the Woo.com API
async function fetchDiscoverPageData(): Promise< ProductGroup[] > {
	let url = '/wc/v3/marketplace/featured';

	if ( LOCALE.userLocale ) {
		url = `${ url }?locale=${ LOCALE.userLocale }`;
	}

	try {
		return ( await apiFetchWithCache( {
			path: url.toString(),
		} ) ) as Promise< ProductGroup[] >;
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

	return (
		fetchJsonWithCache( url.toString() ) as Promise< CategoryAPIItem[] >
	 )
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
	fetchSearchResults,
	fetchDiscoverPageData,
	fetchCategories,
	ProductGroup,
	appendURLParams,
};
