/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import ProductListContent from '../product-list-content/product-list-content';
import ProductListHeader from '../product-list-header/product-list-header';

interface ProductListProps {
	title: string;
}

export default function ProductList( props: ProductListProps ): JSX.Element {
	const { title } = props;

	return (
		<div className="product-list">
			<ProductListHeader title={ title } />
			<ProductListContent />
		</div>
	);
}
