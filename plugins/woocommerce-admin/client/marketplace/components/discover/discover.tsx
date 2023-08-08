/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductList from '../product-list/product-list';
import { fetchDiscoverPageData, ProductGroup } from '../../utils/functions';
import './discover.scss';

export default function Discover(): JSX.Element | null {
	const [ productGroups, setProductGroups ] = useState<
		Array< ProductGroup >
	>( [] );

	useEffect( () => {
		fetchDiscoverPageData().then( ( products: Array< ProductGroup > ) => {
			setProductGroups( products );
		} );
	}, [] );

	if ( ! productGroups.length ) {
		return null;
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
