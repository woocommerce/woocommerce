/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import NoResultsIcon from '../../assets/images/no-results.svg';
import { fetchDiscoverPageData, ProductGroup } from '../../utils/functions';
import ProductLoader from '../product-loader/product-loader';
import ProductList from '../product-list/product-list';
import './no-results.scss';

export default function NoResults(): JSX.Element {
	const [ productGroup, setProductGroup ] = useState< ProductGroup >();
	const [ isLoadingProductGroup, setisLoadingProductGroup ] =
		useState( false );
	const [ noResultsTerm, setNoResultsTerm ] = useState< string >( '' );

	const query = useQuery();

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
				const mostPopularGroup = products.find(
					( group ) => group.id === 'most-popular'
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

		return (
			<ProductList
				title={ productGroup.title }
				products={ productGroup.items }
				groupURL={ productGroup.url }
			/>
		);
	}

	return (
		<div className="woocommerce-marketplace__no-results">
			<div className="woocommerce-marketplace__no-results__content">
				<img
					className="woocommerce-marketplace__no-results__icon"
					src={ NoResultsIcon }
					alt={ __( 'No results.', 'woocommerce' ) }
				/>
				<div className="woocommerce-marketplace__no-results__description">
					<h3 className="woocommerce-marketplace__no-results__description--bold">
						{ sprintf(
							// translators: %s: search term
							__(
								'We didn\'t find any results for "%s"',
								'woocommerce'
							),
							noResultsTerm
						) }
					</h3>
					<p>
						{ __(
							'Try searching again using a different term, or take a look at some of our most popular extensions below.',
							'woocommerce'
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
