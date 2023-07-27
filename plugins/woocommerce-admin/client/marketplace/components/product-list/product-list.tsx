/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import ProductListContent, {
	Product,
} from '../product-list-content/product-list-content';
import ProductListHeader from '../product-list-header/product-list-header';

interface ProductListProps {
	title: string;
	products: Product[];
}

export default function ProductList( props: ProductListProps ): JSX.Element {
	const { title, products } = props;
	return (
		<div className="woocommerce-marketplace__product-list">
			<ProductListHeader title={ title } />
			<ProductListContent products={ products } />
		</div>
	);
}
