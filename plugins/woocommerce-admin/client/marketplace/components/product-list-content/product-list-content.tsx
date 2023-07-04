/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './product-list-content.scss';

export default function ProductListContent(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__product-list-content">
			<div className="woocommerce-marketplace__extension-card"></div>
			<div className="woocommerce-marketplace__extension-card"></div>
			<div className="woocommerce-marketplace__extension-card"></div>
			<div className="woocommerce-marketplace__extension-card"></div>
		</div>
	);
}
