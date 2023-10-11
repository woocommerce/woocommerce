/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import NoResultsExtensionsIcon from '../../assets/images/no-results-extensions.svg';
import NoResultsThemesIcon from '../../assets/images/no-results-themes.svg';
import { fetchDiscoverPageData, ProductGroup } from '../../utils/functions';
import ProductLoader from '../product-loader/product-loader';
import ProductList from '../product-list/product-list';
import './no-results.scss';
import { ProductType } from '../product-list/types';
import CategorySelector from '../category-selector/category-selector';

interface NoResultsProps {
	type: ProductType;
}

export default function NoResults( props: NoResultsProps ): JSX.Element {
	const [ productGroup, setProductGroup ] = useState< ProductGroup >();
	const [ isLoadingProductGroup, setisLoadingProductGroup ] =
		useState( false );
	const [ noResultsTerm, setNoResultsTerm ] = useState< string >( '' );
	const typeLabel =
		props.type === ProductType.theme ? 'themes' : 'extensions';
	const query = useQuery();
	const showCategorySelector = query.tab === 'search' && query.section;

	useEffect( () => {
		if ( query.term ) {
			setNoResultsTerm( query.term );

			return;
		}

		if ( query.category ) {
			/**
			 * Trim understore from start and end of a category. Some categories have underscores at the start and end
			 * and we don't want to show them for the no results term
			 */
			const categoryTerm = query.category.replace( /^_+|_+$/g, '' );

			setNoResultsTerm( categoryTerm );
		}
	}, [ query ] );

	useEffect( () => {
		setisLoadingProductGroup( true );

		fetchDiscoverPageData()
			.then( ( products: ProductGroup[] ) => {
				const productGroupId =
					props.type === ProductType.theme
						? 'popular-themes'
						: 'most-popular';
				const mostPopularGroup = products.find(
					( group ) => group.id === productGroupId
				);

				if ( ! mostPopularGroup ) {
					return;
				}

				mostPopularGroup.items = mostPopularGroup.items.slice( 0, 9 );

				setProductGroup( mostPopularGroup );
			} )
			.catch( () => {
				setProductGroup( undefined );
			} )
			.finally( () => {
				setisLoadingProductGroup( false );
			} );
	}, [] );

	function renderProductGroup() {
		if ( isLoadingProductGroup ) {
			return <ProductLoader />;
		}

		if ( ! productGroup ) {
			return <></>;
		}

		const title = sprintf(
			// translators: %s: product type (themes or extensions)
			__( 'Most popular %s', 'woocommerce' ),
			typeLabel
		);

		return (
			<ProductList
				title={ title }
				products={ productGroup.items }
				groupURL={ productGroup.url }
				type={ productGroup.itemType }
			/>
		);
	}

	function getNoResultsIcon( type: ProductType ) {
		if ( type === ProductType.theme ) {
			return NoResultsThemesIcon;
		}

		return NoResultsExtensionsIcon;
	}

	return (
		<div className="woocommerce-marketplace__no-results">
			{ showCategorySelector && <CategorySelector type={ props.type } /> }
			<div className="woocommerce-marketplace__no-results__content">
				<img
					className="woocommerce-marketplace__no-results__icon"
					src={ getNoResultsIcon( props.type ) }
					alt={ __( 'No results.', 'woocommerce' ) }
					width="80"
					height="80"
				/>
				<div className="woocommerce-marketplace__no-results__description">
					<h3 className="woocommerce-marketplace__no-results__description--bold">
						{ sprintf(
							// translators: %1$s product type (themes or extensions), %2$s: search term
							__(
								"We didn't find %1$s for “%2$s”",
								'woocommerce'
							),
							typeLabel,
							noResultsTerm
						) }
					</h3>
					<p>
						{ sprintf(
							// translators: %s product type (themes or extensions)
							__(
								'Try searching again using a different term, or take a look at some of our most popular %s below.',
								'woocommerce'
							),
							typeLabel
						) }
					</p>
				</div>
			</div>
			<div className="woocommerce-marketplace__no-results__product-group">
				{ renderProductGroup() }
			</div>
		</div>
	);
}
