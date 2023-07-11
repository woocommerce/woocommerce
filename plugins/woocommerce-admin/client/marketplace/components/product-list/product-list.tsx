/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import ProductListContent from '../product-list-content/product-list-content';
import ProductListTitle from '../product-list-title/product-list-title';

interface ProductListProps {
	title: string;
}

export default function ProductList( props: ProductListProps ): JSX.Element {
	const { title } = props;

	return (
		<div className="product-list">
			<ProductListTitle title={ title } />
			<ProductListContent />
		</div>
	);
}
