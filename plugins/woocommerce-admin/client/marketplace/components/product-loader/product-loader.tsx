/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './product-loader.scss';

export default function ProductLoader(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__product-loader">
			<div className="woocommerce-marketplace__product-loader-cards">
				<div className="woocommerce-marketplace__product-loader-divider divider-1"></div>
				<div className="woocommerce-marketplace__product-loader-divider divider-2"></div>
				<div className="woocommerce-marketplace__product-loader-divider divider-3"></div>
			</div>
		</div>
	);
}
