/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export interface ShippingPlaceholderProps {
	showCalculator: boolean;
	isCheckout?: boolean;
	addressComplete?: boolean;
}

export const ShippingPlaceholder = ( {
	showCalculator,
	addressComplete,
	isCheckout = false,
}: ShippingPlaceholderProps ): JSX.Element | null => {
	if ( showCalculator ) {
		return null;
	}

	if ( isCheckout ) {
		return (
			<span className="wc-block-components-totals-item-shipping-placeholder">
				{ addressComplete
					? __( 'No available delivery option', 'woocommerce' )
					: __( 'Enter the address to calculate', 'woocommerce' ) }
			</span>
		);
	}

	return (
		<span className="wc-block-components-totals-item-shipping-placeholder">
			{ __( 'Calculated during checkout', 'woocommerce' ) }
		</span>
	);
};

export default ShippingPlaceholder;
