/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import ProductList from '../product-list/product-list';
import './extensions.scss';

export default function Extensions(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__extensions">
			<ProductList title="Extensions" />
		</div>
	);
}
