/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import CategorySelector from '../category-selector/category-selector';
import ProductListContent from '../product-list-content/product-list-content';
import ProductListHeader from '../product-list-header/product-list-header';

interface ProductListProps {
	title: string;
}

export default function ProductList( props: ProductListProps ): JSX.Element {
	const { title } = props;

	return (
		<div className="woocommerce-marketplace__product-list">
			<ProductListHeader title={ title } />
			<CategorySelector />
			<ProductListContent />
		</div>
	);
}
