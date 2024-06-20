/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WooPaymentMethodLogos } from '../WooPaymentMethodLogos/WooPaymentMethodLogos';

export const PaymentMethodsIcons: React.VFC< {
	isWooPayEligible: boolean;
} > = ( { isWooPayEligible = false } ) => {
	return (
		<div className="woocommerce-recommended-payments-banner__footer_icon_container">
			<WooPaymentMethodLogos
				isWooPayEligible={ isWooPayEligible }
				maxNrElements={ 10 }
			/>
		</div>
	);
};
