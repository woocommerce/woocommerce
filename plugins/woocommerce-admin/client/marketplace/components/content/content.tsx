/**
 * External dependencies
 */
import { useContext, useEffect, useState } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './content.scss';

import { Product, SearchAPIProductType } from '../product-list/types';
import { MARKETPLACE_SEARCH_API_PATH, MARKETPLACE_HOST } from '../constants';
import { getAdminSetting } from '../../../utils/admin-settings';
import Discover from '../discover/discover';
import Extensions from '../extensions/extensions';
import Themes from '../themes/themes';
import { MarketplaceContext } from '../../contexts/marketplace-context';

const renderContent = (
	selectedTab?: string,
	products?: Product[],
	isLoading?: boolean
): JSX.Element => {
	switch ( selectedTab ) {
		case 'extensions':
			return <Extensions products={ products } isLoading={ isLoading } />;
		case 'themes':
			return <Themes products={ products } isLoading={ isLoading } />;
		default:
			return <Discover />;
	}
};

export default function Content(): JSX.Element {
	const marketplaceContextValue = useContext( MarketplaceContext );

	const [ productList, setProductList ] = useState< Product[] >( [] );
	const { isLoading, setIsLoading } = marketplaceContextValue;

	const { selectedTab } = marketplaceContextValue;
	const query = useQuery();

	// Get the content for this screen
	useEffect( () => {
		setIsLoading( true );

		const params = new URLSearchParams();

		if ( query.term ) {
			params.append( 'term', query.term );
		}

		if ( query.category ) {
			params.append( 'category', query.category );
		} else if ( selectedTab === 'themes' ) {
			params.append( 'category', 'themes' );
		}

		const wccomSettings = getAdminSetting( 'wccomHelper', false );
		if ( wccomSettings.storeCountry ) {
			params.append( 'country', wccomSettings.storeCountry );
		}

		const wccomSearchEndpoint =
			MARKETPLACE_HOST +
			MARKETPLACE_SEARCH_API_PATH +
			'?' +
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
							image: product.image.replace( '.test', '.com' ),
							type: product.type,
							description: product.excerpt,
							vendorName: product.vendor_name,
							vendorUrl: product.vendor_url,
							icon: product.icon.replace( '.test', '.com' ),
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
	}, [ query ] );
	return (
		<div className="woocommerce-marketplace__content">
			{ renderContent( selectedTab, productList, isLoading ) }
		</div>
	);
}
