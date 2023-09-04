/**
 * Internal dependencies
 */
import ProductListContent from '../product-list-content/product-list-content';
import ProductListHeader from '../product-list-header/product-list-header';
import { Product } from './types';

interface ProductListProps {
	title: string;
	products: Product[];
	groupURL: string;
}

export default function ProductList( props: ProductListProps ): JSX.Element {
	const { title, products, groupURL } = props;

	return (
		<div className="woocommerce-marketplace__product-list">
			<ProductListHeader title={ title } groupURL={ groupURL } />
			<ProductListContent products={ products } />
		</div>
	);
}
