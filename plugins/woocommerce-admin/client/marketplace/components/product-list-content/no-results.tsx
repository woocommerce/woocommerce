/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { fetchDiscoverPageData, ProductGroup } from '../../utils/functions';
import ProductLoader from '../product-loader/product-loader';
import ProductList from '../product-list/product-list';
import { ProductType, SearchResultType } from '../product-list/types';
import CategorySelector from '../category-selector/category-selector';
import './no-results.scss';

export default function NoResults( props: {
	type: SearchResultType;
	showHeading?: boolean;
	heading?: string;
} ): JSX.Element {
	const [ productGroups, setProductGroups ] = useState< ProductGroup[] >();
	const [ isLoading, setIsLoading ] = useState( false );
	const query = useQuery();
	const showCategorySelector = query.tab === 'search' && query.section;
	const productGroupsForSearchType = {
		[ SearchResultType.all ]: [
			'most-popular',
			'popular-themes',
			'business-services',
		],
		[ SearchResultType.theme ]: [ 'popular-themes' ],
		[ SearchResultType.extension ]: [ 'most-popular' ],
		[ SearchResultType.businessService ]: [ 'business-services' ],
	};

	useEffect( () => {
		setIsLoading( true );

		fetchDiscoverPageData()
			.then( ( products: ProductGroup[] ) => {
				const productGroupIds =
					productGroupsForSearchType[ props.type ];

				if ( ! productGroupIds ) {
					return;
				}

				const productGroupsToDisplay = products.filter( ( group ) => {
					return productGroupIds.includes( group.id );
				} );

				if ( ! productGroupsToDisplay ) {
					return;
				}

				// Limit productGroup.items to 4 items.
				productGroupsToDisplay.forEach( ( group ) => {
					group.items = group.items.slice( 0, 4 );
				} );

				setProductGroups( productGroupsToDisplay );
			} )
			.catch( () => {
				setProductGroups( undefined );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	function productListTitle( groupId: string ) {
		if ( groupId === 'popular-themes' ) {
			return __( 'Our favorite themes', 'woocommerce' );
		} else if ( groupId === 'business-services' ) {
			return __( 'Services to help your business grow', 'woocommerce' );
		}

		return __( 'Most popular extensions', 'woocommerce' );
	}

	function renderProductGroups() {
		if ( isLoading ) {
			return (
				<>
					<ProductLoader
						type={ ProductType.extension }
						placeholderCount={ 4 }
					/>
					<ProductLoader
						type={ ProductType.theme }
						placeholderCount={ 4 }
					/>
					<ProductLoader
						type={ ProductType.businessService }
						placeholderCount={ 4 }
					/>
				</>
			);
		}

		if ( ! productGroups || productGroups.length === 0 ) {
			return <></>;
		}

		return (
			<>
				{ productGroups.map( ( productGroup ) => {
					return (
						<ProductList
							title={ productListTitle( productGroup.id ) }
							products={ productGroup.items }
							groupURL={ productGroup.url }
							productGroup={ productGroup.id }
							type={ productGroup.itemType }
							key={ productGroup.id }
						/>
					);
				} ) }
			</>
		);
	}

	function categorySelector() {
		if ( ! showCategorySelector ) {
			return <></>;
		}

		if ( props.type === SearchResultType.all ) {
			return <></>;
		}

		let categorySelectorType = ProductType.extension;

		if ( props.type === SearchResultType.theme ) {
			categorySelectorType = ProductType.theme;
		}

		if ( props.type === SearchResultType.businessService ) {
			categorySelectorType = ProductType.businessService;
		}

		return <CategorySelector type={ categorySelectorType } />;
	}

	return (
		<div className="woocommerce-marketplace__no-results">
			{ categorySelector() }
			<div className="woocommerce-marketplace__no-results__content">
				<h2 className="woocommerce-marketplace__no-results__heading">
					{ props.showHeading ? props.heading : '' }
				</h2>
				<p className="woocommerce-marketplace__no-results__description">
					{ __(
						'Try searching again using a different term, or take a look at' +
							' our recommendations below.',
						'woocommerce'
					) }
				</p>
			</div>
			<div className="woocommerce-marketplace__no-results__product-groups">
				{ renderProductGroups() }
			</div>
		</div>
	);
}
