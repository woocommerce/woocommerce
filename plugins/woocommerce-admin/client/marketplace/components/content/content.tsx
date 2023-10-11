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
import SearchResults from '../search-results/search-results';
import Themes from '../themes/themes';
import MySubscriptions from '../my-subscriptions/my-subscriptions';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { SubscriptionsContextProvider } from '../../contexts/subscriptions-context';

export default function Content(): JSX.Element {
	const marketplaceContextValue = useContext( MarketplaceContext );
	const [ products, setProducts ] = useState< Product[] >( [] );
	const { setIsLoading, selectedTab } = marketplaceContextValue;
	const query = useQuery();

	// Get the content for this screen
	useEffect( () => {
		if ( [ '', 'discover' ].includes( selectedTab ) ) {
			return;
		}

		setIsLoading( true );
		setProducts( [] );

		const params = new URLSearchParams();

		if ( query.term ) {
			params.append( 'term', query.term );
		}

		if ( query.category ) {
			params.append(
				'category',
				query.category === '_all' ? '' : query.category
			);
		} else if ( selectedTab === 'themes' ) {
			params.append( 'category', 'themes' );
		} else if ( selectedTab === 'search' ) {
			params.append( 'category', 'extensions-themes' );
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
				const productList = response.products.map(
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
				setProducts( productList );
			} )
			.catch( () => {
				setProducts( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ query.term, query.category, selectedTab, setIsLoading ] );

	const renderContent = (): JSX.Element => {
		switch ( selectedTab ) {
			case 'extensions':
				return <Extensions products={ products } />;
			case 'themes':
				return <Themes products={ products } />;
			case 'search':
				return <SearchResults products={ products } />;
			case 'discover':
				return <Discover />;
			case 'my-subscriptions':
				return (
					<SubscriptionsContextProvider>
						<MySubscriptions />
					</SubscriptionsContextProvider>
				);
			default:
				return <></>;
		}
	};

	return (
		<div className="woocommerce-marketplace__content">
			{ renderContent() }
		</div>
	);
}
