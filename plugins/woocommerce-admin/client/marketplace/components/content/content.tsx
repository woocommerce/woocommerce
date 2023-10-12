/**
 * External dependencies
 */
import { useContext, useEffect, useState } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './content.scss';
import { Product, ProductType, SearchResultType } from '../product-list/types';
import { getAdminSetting } from '../../../utils/admin-settings';
import Discover from '../discover/discover';
import Products from '../products/products';
import SearchResults from '../search-results/search-results';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { fetchSearchResults } from '../../utils/functions';

export default function Content(): JSX.Element {
	const marketplaceContextValue = useContext( MarketplaceContext );
	const [ products, setProducts ] = useState< Product[] >( [] );
	const { setIsLoading, selectedTab } = marketplaceContextValue;
	const query = useQuery();

	// Get the content for this screen
	useEffect( () => {
		const abortController = new AbortController();
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

		fetchSearchResults( params, abortController.signal )
			.then( ( productList ) => {
				setProducts( productList );
			} )
			.catch( () => {
				setProducts( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
		return () => {
			abortController.abort();
		};
	}, [ query.term, query.category, selectedTab, setIsLoading ] );

	const renderContent = (): JSX.Element => {
		switch ( selectedTab ) {
			case 'extensions':
				return (
					<Products
						products={ products }
						categorySelector={ true }
						type={ ProductType.extension }
					/>
				);
			case 'themes':
				return (
					<Products
						products={ products }
						categorySelector={ true }
						type={ ProductType.theme }
					/>
				);
			case 'search':
				return (
					<SearchResults
						products={ products }
						type={ SearchResultType.all }
					/>
				);
			case 'discover':
				return <Discover />;
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
