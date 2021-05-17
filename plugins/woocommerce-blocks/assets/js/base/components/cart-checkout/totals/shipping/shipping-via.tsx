/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const ShippingVia = ( {
	selectedShippingRates,
}: {
	selectedShippingRates: string[];
} ): JSX.Element => {
	return (
		<div className="wc-block-components-totals-item__description wc-block-components-totals-shipping__via">
			{ __( 'via', 'woo-gutenberg-products-block' ) }{ ' ' }
			{ selectedShippingRates.join( ', ' ) }
		</div>
	);
};
