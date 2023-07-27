/**
 * Internal dependencies
 */
import { Product } from '../components/product-list-content/product-list-content';

interface ProductCategory {
	id: number;
	title: string;
	items: Product[];
}

// Fecth data for the discover page from the WooCommerce.com API
const fetchDiscoverPageData = async (): Promise< ProductCategory > => {
	const fetchUrl =
		'https://woocommerce.com/wp-json/wccom-extensions/2.0/featured';

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

export { fetchDiscoverPageData, ProductCategory };
