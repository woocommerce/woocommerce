/**
 * External dependencies
 */
import {
	useContext,
	useEffect,
	useState,
	useCallback,
} from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './content.scss';
import { Product, ProductType } from '../product-list/types';
import { getAdminSetting } from '~/utils/admin-settings';
import Discover from '../discover/discover';
import Products from '../products/products';
import MySubscriptions from '../my-subscriptions/my-subscriptions';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import { fetchSearchResults } from '../../utils/functions';
import { SubscriptionsContextProvider } from '../../contexts/subscriptions-context';
import { SearchResultsCountType } from '../../contexts/types';

import {
	recordMarketplaceView,
	recordLegacyTabView,
} from '../../utils/tracking';
import InstallNewProductModal from '../install-flow/install-new-product-modal';
import Promotions from '../promotions/promotions';
import ConnectNotice from '~/marketplace/components/connect-notice/connect-notice';
import PluginInstallNotice from '../woo-update-manager-plugin/plugin-install-notice';
import SubscriptionsExpiredExpiringNotice from '~/marketplace/components/my-subscriptions/subscriptions-expired-expiring-notice';
import LoadMoreButton from '../load-more-button/load-more-button';

export default function Content(): JSX.Element {
	const marketplaceContextValue = useContext( MarketplaceContext );
	const [ allProducts, setAllProducts ] = useState< Product[] >( [] );
	const [ filteredProducts, setFilteredProducts ] = useState< Product[] >(
		[]
	);
	const [ currentPage, setCurrentPage ] = useState( 1 );
	const [ totalPagesCategory, setTotalPagesCategory ] = useState( 1 );
	const [ totalPagesExtensions, setTotalPagesExtensions ] = useState( 1 );
	const [ totalPagesThemes, setTotalPagesThemes ] = useState( 1 );
	const [ totalPagesBusinessServices, setTotalPagesBusinessServices ] =
		useState( 1 );

	const {
		setIsLoading,
		selectedTab,
		setHasBusinessServices,
		setSearchResultsCount,
	} = marketplaceContextValue;
	const query = useQuery();

	const tagProductsWithType = (
		products: Product[],
		type: ProductType
	): Product[] => {
		return products.map( ( product ) => ( {
			...product,
			type,
		} ) );
	};

	useEffect( () => {
		const categories: Array< {
			category: keyof SearchResultsCountType;
			type: ProductType;
		} > = [
			{ category: 'extensions', type: ProductType.extension },
			{ category: 'themes', type: ProductType.theme },
			{
				category: 'business-services',
				type: ProductType.businessService,
			},
		];
		const abortControllers = categories.map( () => new AbortController() );

		setIsLoading( true );
		setAllProducts( [] );

		// If query.category is present and not '_all', only fetch that category
		if ( query.category && query.category !== '_all' ) {
			const params = new URLSearchParams();

			params.append( 'category', query.category );

			if ( query.term ) {
				params.append( 'term', query.term );
			}

			const wccomSettings = getAdminSetting( 'wccomHelper', false );
			if ( wccomSettings.storeCountry ) {
				params.append( 'country', wccomSettings.storeCountry );
			}

			fetchSearchResults( params, abortControllers[ 0 ].signal )
				.then( ( productList ) => {
					setAllProducts( productList.products );
					setTotalPagesCategory( productList.totalPages );
					setSearchResultsCount( {
						extensions: productList.products.filter(
							( p ) => p.type === ProductType.extension
						).length,
						themes: productList.products.filter(
							( p ) => p.type === ProductType.theme
						).length,
						'business-services': productList.products.filter(
							( p ) => p.type === ProductType.businessService
						).length,
					} );
				} )
				.catch( () => {
					setAllProducts( [] );
				} )
				.finally( () => {
					setIsLoading( false );
				} );
		} else {
			// Fetch all tabs when query.term or query.category changes
			Promise.all(
				categories.map( ( { category, type }, index ) => {
					const params = new URLSearchParams();
					if ( category !== 'extensions' ) {
						params.append( 'category', category );
					}
					if ( query.term ) {
						params.append( 'term', query.term );
					}

					const wccomSettings = getAdminSetting(
						'wccomHelper',
						false
					);
					if ( wccomSettings.storeCountry ) {
						params.append( 'country', wccomSettings.storeCountry );
					}

					return fetchSearchResults(
						params,
						abortControllers[ index ].signal
					).then( ( productList ) => {
						const typedProducts = tagProductsWithType(
							productList.products,
							type
						);
						if ( category === 'business-services' ) {
							setHasBusinessServices( typedProducts.length > 0 );
						}
						return {
							products: typedProducts,
							totalPages: productList.totalPages,
							type,
						};
					} );
				} )
			)
				.then( ( results ) => {
					const combinedProducts = results.flatMap(
						( result ) => result.products
					);
					setAllProducts( combinedProducts );
					setSearchResultsCount( {
						extensions: combinedProducts.filter(
							( p ) => p.type === ProductType.extension
						).length,
						themes: combinedProducts.filter(
							( p ) => p.type === ProductType.theme
						).length,
						'business-services': combinedProducts.filter(
							( p ) => p.type === ProductType.businessService
						).length,
					} );
					results.forEach( ( result ) => {
						switch ( result.type ) {
							case ProductType.extension:
								setTotalPagesExtensions( result.totalPages );
								break;
							case ProductType.theme:
								setTotalPagesThemes( result.totalPages );
								break;
							case ProductType.businessService:
								setTotalPagesBusinessServices(
									result.totalPages
								);
								break;
						}
					} );
				} )
				.catch( () => {
					setAllProducts( [] );
				} )
				.finally( () => {
					setIsLoading( false );
				} );
		}

		return () => {
			abortControllers.forEach( ( controller ) => {
				controller.abort();
			} );
		};
	}, [
		query.term,
		query.category,
		setHasBusinessServices,
		setIsLoading,
		setSearchResultsCount,
		currentPage,
	] ); // Depend on term and category

	// Filter the products based on the selected tab
	useEffect( () => {
		let filtered: Product[] | null;
		switch ( selectedTab ) {
			case 'extensions':
				filtered = allProducts.filter(
					( p ) => p.type === ProductType.extension
				);
				break;
			case 'themes':
				filtered = allProducts.filter(
					( p ) => p.type === ProductType.theme
				);
				break;
			case 'business-services':
				filtered = allProducts.filter(
					( p ) => p.type === ProductType.businessService
				);
				break;
			default:
				filtered = [];
		}
		setFilteredProducts( filtered );
	}, [ selectedTab, allProducts ] );

	// Record tab view events when the query changes
	useEffect( () => {
		const marketplaceViewProps = {
			view: query?.tab,
			search_term: query?.term,
			product_type: query?.section,
			category: query?.category,
		};
		recordMarketplaceView( marketplaceViewProps );
		recordLegacyTabView( marketplaceViewProps );
	}, [ query?.tab, query?.term, query?.section, query?.category ] );

	// Reset current page when tab or category changes
	// TODO: Might need to reset it when query.term changes as well?
	useEffect( () => {
		setCurrentPage( 1 );
	}, [ selectedTab, query?.category ] );

	const renderContent = (): JSX.Element => {
		switch ( selectedTab ) {
			case 'extensions':
				return (
					<Products
						products={ filteredProducts }
						categorySelector={ true }
						type={ ProductType.extension }
					/>
				);
			case 'themes':
				return (
					<Products
						products={ filteredProducts }
						categorySelector={ true }
						type={ ProductType.theme }
					/>
				);
			case 'business-services':
				return (
					<Products
						products={ filteredProducts }
						categorySelector={ true }
						type={ ProductType.businessService }
					/>
				);
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

	const loadMoreProducts = useCallback( () => {
		setCurrentPage( ( prevPage ) => prevPage + 1 );
	}, [] );

	const shouldShowLoadMoreButton = () => {
		if ( ! query.category || query.category === '_all' ) {
			// Check against total pages for the selected tab
			switch ( selectedTab ) {
				case 'extensions':
					return currentPage < totalPagesExtensions;
				case 'themes':
					return currentPage < totalPagesThemes;
				case 'business-services':
					return currentPage < totalPagesBusinessServices;
				default:
					return false;
			}
		} else {
			// Check against totalPagesCategory for specific category
			return currentPage < totalPagesCategory;
		}
	};

	return (
		<div className="woocommerce-marketplace__content">
			<Promotions />
			<InstallNewProductModal products={ filteredProducts } />
			{ selectedTab !== 'business-services' &&
				selectedTab !== 'my-subscriptions' && <ConnectNotice /> }
			{ selectedTab !== 'business-services' && <PluginInstallNotice /> }
			{ selectedTab !== 'business-services' && (
				<SubscriptionsExpiredExpiringNotice type="expired" />
			) }
			{ selectedTab !== 'business-services' && (
				<SubscriptionsExpiredExpiringNotice type="expiring" />
			) }

			{ renderContent() }
			{ shouldShowLoadMoreButton() && (
				<LoadMoreButton onLoadMore={ loadMoreProducts } />
			) }
		</div>
	);
}
