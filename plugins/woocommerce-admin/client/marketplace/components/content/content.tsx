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
import { speak } from '@wordpress/a11y';
import { __, sprintf } from '@wordpress/i18n';

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
import { fetchSearchResults, getProductType } from '../../utils/functions';
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
	const [ firstNewProductId, setFirstNewProductId ] = useState< number >( 0 );
	const [ isLoadingMore, setIsLoadingMore ] = useState( false );

	const {
		isLoading,
		setIsLoading,
		selectedTab,
		setHasBusinessServices,
		setSearchResultsCount,
	} = marketplaceContextValue;
	const query = useQuery();

	const searchCompleteAnnouncement = ( count: number ): void => {
		speak(
			sprintf(
				// translators: %d is the number of products found.
				__( '%d products found', 'woocommerce' ),
				count
			)
		);
	};

	const tagProductsWithType = (
		products: Product[],
		type: ProductType
	): Product[] => {
		return products.map( ( product ) => ( {
			...product,
			type,
		} ) );
	};

	const loadMoreProducts = useCallback( () => {
		setIsLoadingMore( true );
		const params = new URLSearchParams();
		const abortController = new AbortController();

		if ( query.category && query.category !== '_all' ) {
			params.append( 'category', query.category );
		}

		if ( query.tab === 'themes' || query.tab === 'business-services' ) {
			params.append( 'category', query.tab );
		}

		if ( query.term ) {
			params.append( 'term', query.term );
		}

		const wccomSettings = getAdminSetting( 'wccomHelper', false );
		if ( wccomSettings.storeCountry ) {
			params.append( 'country', wccomSettings.storeCountry );
		}

		params.append( 'page', ( currentPage + 1 ).toString() );

		fetchSearchResults( params, abortController.signal )
			.then( ( productList ) => {
				setAllProducts( ( prevProducts ) => {
					const flattenedPrevProducts = Array.isArray(
						prevProducts[ 0 ]
					)
						? prevProducts.flat()
						: prevProducts;

					const newProducts = productList.products.filter(
						( newProduct ) =>
							! flattenedPrevProducts.some(
								( prevProduct ) =>
									prevProduct.id === newProduct.id
							)
					);

					if ( newProducts.length > 0 ) {
						setFirstNewProductId( newProducts[ 0 ].id ?? 0 );
					}

					const combinedProducts = [
						...flattenedPrevProducts,
						...newProducts,
					];

					return combinedProducts;
				} );

				speak( __( 'More products loaded', 'woocommerce' ) );
				setCurrentPage( ( prevPage ) => prevPage + 1 );
				setIsLoadingMore( false );
			} )
			.catch( () => {
				speak( __( 'Error loading more products', 'woocommerce' ) );
			} )
			.finally( () => {
				setIsLoadingMore( false );
			} );

		return () => {
			abortController.abort();
		};
	}, [
		currentPage,
		query.category,
		query.term,
		query.tab,
		setIsLoadingMore,
	] );

	useEffect( () => {
		// if it's a paginated request, don't use this effect
		if ( currentPage > 1 ) {
			return;
		}

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
						[ query.tab ]: productList.totalProducts,
					} );

					searchCompleteAnnouncement( productList.totalProducts );
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
							totalProducts: productList.totalProducts,
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
						extensions: results.find(
							( i ) => i.type === 'extension'
						)?.totalProducts,
						themes: results.find( ( i ) => i.type === 'theme' )
							?.totalProducts,
						'business-services': results.find(
							( i ) => i.type === 'business-service'
						)?.totalProducts,
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

					searchCompleteAnnouncement(
						results.reduce( ( acc, curr ) => {
							return acc + curr.totalProducts;
						}, 0 )
					);
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
		query.tab,
		query.term,
		query.category,
		setHasBusinessServices,
		setIsLoading,
		setSearchResultsCount,
		currentPage,
	] );

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

	// Reset current page when tab, term, or category changes
	useEffect( () => {
		setCurrentPage( 1 );
		setFirstNewProductId( 0 );
	}, [ selectedTab, query?.category, query?.term ] );

	// Maintain product focus for accessibility
	useEffect( () => {
		if ( firstNewProductId ) {
			setTimeout( () => {
				const firstNewProduct = document.getElementById(
					`product-${ firstNewProductId }`
				);
				if ( firstNewProduct ) {
					firstNewProduct.focus();
				}
			}, 0 );
		}
	}, [ firstNewProductId ] );

	const renderContent = (): JSX.Element => {
		switch ( selectedTab ) {
			case 'extensions':
			case 'themes':
			case 'business-services':
				return (
					<Products
						products={ filteredProducts }
						categorySelector={ true }
						type={ getProductType( selectedTab ) }
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
			{ ! isLoading && shouldShowLoadMoreButton() && (
				<LoadMoreButton
					onLoadMore={ loadMoreProducts }
					isBusy={ isLoadingMore }
					disabled={ isLoadingMore }
				/>
			) }
		</div>
	);
}
