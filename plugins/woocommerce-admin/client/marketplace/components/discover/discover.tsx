/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductList from '../product-list/product-list';
import { fetchDiscoverPageData, ProductGroup } from '../../utils/functions';
import ProductLoader from '../product-loader/product-loader';
import './discover.scss';

export default function Discover(): JSX.Element | null {
	const [ productGroups, setProductGroups ] = useState<
		Array< ProductGroup >
	>( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	useEffect( () => {
		setIsLoading( true );

		fetchDiscoverPageData()
			.then( ( products: Array< ProductGroup > ) => {
				setProductGroups( products );
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	if ( isLoading ) {
		return <ProductLoader />;
	}

	const groupsList = productGroups.flatMap( ( group ) => group );
	return (
		<div className="woocommerce-marketplace__discover">
			{ groupsList.map( ( groups ) => (
				<ProductList
					key={ groups.id }
					title={ groups.title }
					products={ groups.items }
					groupURL={ groups.url }
				/>
			) ) }
		</div>
	);
}
