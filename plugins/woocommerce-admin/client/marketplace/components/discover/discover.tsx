/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductList from '../product-list/product-list';
import { fetchDiscoverPageData, ProductCategory } from '../../utils/functions';
import './discover.scss';

export default function Discover(): JSX.Element | null {
	const [ productCategory, setProductCategory ] = useState<
		ProductCategory[]
	>( [] );

	useEffect( () => {
		fetchDiscoverPageData().then( ( products: ProductCategory ) => {
			setProductCategory( [ products ] );
		} );
	}, [] );

	if ( ! productCategory.length ) {
		return null;
	}

	const productList = productCategory.flatMap( ( group ) => group );

	return (
		<div className="woocommerce-marketplace__discover">
			{ productList.map( ( products ) => (
				<ProductList
					key={ products.id }
					title={ products.title }
					products={ products.items }
				/>
			) ) }
		</div>
	);
}
