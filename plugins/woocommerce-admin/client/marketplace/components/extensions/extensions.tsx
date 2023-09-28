/**
 * External dependencies
 */
import { useContext, useEffect, useState } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './extensions.scss';
import CategorySelector from '../category-selector/category-selector';
import { MarketplaceContext } from '../../contexts/marketplace-context';
import ProductListContent from '../product-list-content/product-list-content';
import ProductLoader from '../product-loader/product-loader';
import NoResults from '../product-list-content/no-results';
import { Product, SearchAPIProductType } from '../product-list/types';
import { fetchSearchResults } from '../../utils/functions';
import { getAdminSetting } from '../../../utils/admin-settings';

export default function Extensions(): JSX.Element {
	const [ productList, setProductList ] = useState< Product[] >( [] );
	const marketplaceContextValue = useContext( MarketplaceContext );
	const { isLoading, setIsLoading } = marketplaceContextValue;

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
		}

		const wccomSettings = getAdminSetting( 'wccomHelper', false );
		if ( wccomSettings.storeCountry ) {
			params.append( 'country', wccomSettings.storeCountry );
		}

		fetchSearchResults( params )
			.then( ( products ) => {
				setProductList( products );
			} )
			.catch( () => {
				setProductList( [] );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [ query ] );

	const products = productList.slice( 0, 60 );

	let title = __( '0 extensions found', 'woocommerce' );

	if ( products.length > 0 ) {
		title = sprintf(
			// translators: %s: number of extensions
			_n(
				'%s extension',
				'%s extensions',
				products.length,
				'woocommerce'
			),
			products.length
		);
	}

	function content() {
		if ( isLoading ) {
			return <ProductLoader />;
		}

		if ( products.length === 0 ) {
			return <NoResults />;
		}

		return (
			<>
				<CategorySelector />
				<ProductListContent products={ products } />
			</>
		);
	}

	return (
		<div className="woocommerce-marketplace__extensions">
			<h2 className="woocommerce-marketplace__product-list-title  woocommerce-marketplace__product-list-title--extensions">
				{ title }
			</h2>
			{ content() }
		</div>
	);
}
