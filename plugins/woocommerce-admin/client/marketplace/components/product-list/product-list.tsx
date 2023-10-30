/**
 * Internal dependencies
 */
import ProductListContent from '../product-list-content/product-list-content';
import ProductListHeader from '../product-list-header/product-list-header';
import { Product, ProductType } from './types';

interface ProductListProps {
	title: string;
	products: Product[];
	groupURL: string;
	type: ProductType;
}

export default function ProductList( props: ProductListProps ): JSX.Element {
	const { title, products, groupURL, type } = props;

	return (
		<div className="woocommerce-marketplace__product-list">
			<ProductListHeader title={ title } groupURL={ groupURL } />
			<ProductListContent products={ products } type={ type } />
		</div>
	);
}
