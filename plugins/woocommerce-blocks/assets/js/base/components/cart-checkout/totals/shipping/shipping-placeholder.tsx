/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export interface ShippingPlaceholderProps {
	showCalculator: boolean;
	isCheckout?: boolean;
}

export const ShippingPlaceholder = ( {
	showCalculator,

	isCheckout = false,
}: ShippingPlaceholderProps ): JSX.Element | null => {
	if ( showCalculator ) {
		return null;
	}

	return (
		<span>
			{ isCheckout
				? __( 'No shipping options available', 'woocommerce' )
				: __( 'Calculated during checkout', 'woocommerce' ) }
		</span>
	);
};

export default ShippingPlaceholder;
