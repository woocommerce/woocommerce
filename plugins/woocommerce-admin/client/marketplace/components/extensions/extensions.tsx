/**
 * External dependencies
 */
import { useContext } from 'react';

/**
 * Internal dependencies
 */
import './extensions.scss';
import CategorySelector from '../category-selector/category-selector';
import { ProductListContext } from '../../contexts/product-list-context';
import ProductListContent from '../product-list-content/product-list-content';

export default function Extensions(): JSX.Element {
	const productListContextValue = useContext( ProductListContext );

	let { productList } = productListContextValue;
	productList = productList.splice( 0, 21 );

	return (
		<div className="woocommerce-marketplace__extensions">
			<CategorySelector />
			<ProductListContent products={ productList } />
		</div>
	);
}
