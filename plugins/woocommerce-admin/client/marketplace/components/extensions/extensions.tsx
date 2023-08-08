/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './extensions.scss';
import CategorySelector from '../category-selector/category-selector';

export default function Extensions(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__extensions">
			<CategorySelector />
			<div className="woocommerce-marketplace__product-list-content">
				<div className="woocommerce-marketplace__extension-card"></div>
				<div className="woocommerce-marketplace__extension-card"></div>
				<div className="woocommerce-marketplace__extension-card"></div>
				<div className="woocommerce-marketplace__extension-card"></div>
			</div>
		</div>
	);
}
