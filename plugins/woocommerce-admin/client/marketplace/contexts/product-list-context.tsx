/**
 * External dependencies
 */
import { useQuery } from '@woocommerce/navigation';
import { useState, useEffect, createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	Product,
	SearchAPIProductType,
} from '../components/product-list/types';
import { MARKETPLACE_URL } from '../components/constants';

type ProductListContextType = {
	productList: Product[];
	isLoading: boolean;
};

export const ProductListContext = createContext< ProductListContextType >( {
	productList: [],
	isLoading: false,
} );

type ProductListContextProviderProps = {
	children: JSX.Element;
	country?: string;
	locale?: string;
};

export function ProductListContextProvider(
	props: ProductListContextProviderProps
): JSX.Element {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ productList, setProductList ] = useState< Product[] >( [] );

	const contextValue = {
		productList,
		isLoading,
	};

	const query = useQuery();

	useEffect( () => {
		setIsLoading( true );

		const params = new URLSearchParams();

		params.append( 'term', query.term ?? '' );
		params.append( 'country', props.country ?? '' );
		params.append( 'locale', props.locale ?? '' );

		if ( query.category ) {
			params.append( 'category', query.category );
		}

		const wccomSearchEndpoint =
			MARKETPLACE_URL +
			'/wp-json/wccom-extensions/1.0/search?' +
			params.toString();

		// Fetch data from WCCOM API
		fetch( wccomSearchEndpoint )
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				/**
				 * Product card component expects a Product type.
				 * So we build that object from the API response.
				 */
				const products = response.products.map(
					( product: SearchAPIProductType ): Product => {
						return {
							id: product.id,
							title: product.title,
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

				setProductList( products );
			} )
			.catch( () => {
				setProductList( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ query, props.country, props.locale ] );

	return (
		<ProductListContext.Provider value={ contextValue }>
			{ props.children }
		</ProductListContext.Provider>
	);
}
