/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import { Button } from '@ariakit/react';

/**
 * Internal dependencies
 */
import NoPaymentMethods from './no-payment-methods';
import PaymentMethodOptions from './payment-method-options';
import SavedPaymentMethodOptions from './saved-payment-method-options';
import './style.scss';

/**
 * PaymentMethods component.
 *
 * @return {*} The rendered component.
 */
const PaymentMethods = () => {
	const [ showPaymentMethodsToggle, setShowPaymentMethodsToggle ] =
		useState( false );
	const {
		paymentMethodsInitialized,
		availablePaymentMethods,
		hasSavedPaymentMethods,
		isExpressPaymentMethodActive,
		activeSavedToken,
	} = useSelect( ( select ) => {
		const store = select( PAYMENT_STORE_KEY );
		return {
			paymentMethodsInitialized: store.paymentMethodsInitialized(),
			activeSavedToken: store.getActiveSavedToken(),
			availablePaymentMethods: store.getAvailablePaymentMethods(),
			hasSavedPaymentMethods:
				Object.keys( store.getSavedPaymentMethods() || {} ).length > 0,
			isExpressPaymentMethodActive: store.isExpressPaymentMethodActive(),
		};
	} );

	// If using an express payment method, don't show the regular payment methods.
	if ( isExpressPaymentMethodActive ) {
		return null;
	}

	if (
		paymentMethodsInitialized &&
		Object.keys( availablePaymentMethods ).length === 0
	) {
		return <NoPaymentMethods />;
	}

	// Show payment methods if the toggle is on or if there are no saved payment methods, or if the active saved token is not set.
	const showPaymentMethods =
		showPaymentMethodsToggle ||
		! hasSavedPaymentMethods ||
		( paymentMethodsInitialized && ! activeSavedToken );

	return (
		<>
			{ hasSavedPaymentMethods && (
				<>
					<SavedPaymentMethodOptions />
					<p className="wc-block-components-checkout-step__description wc-block-components-checkout-step__description-payments-aligned">
						<Button
							render={ <span /> }
							type="button"
							className="wc-block-components-show-payment-methods__link"
							onClick={ ( e ) => {
								e.preventDefault();
								setShowPaymentMethodsToggle(
									! showPaymentMethodsToggle
								);
							} }
							aria-label={ __(
								'Use another payment method',
								'woocommerce'
							) }
							aria-expanded={ showPaymentMethodsToggle }
						>
							{ __(
								'Use another payment method',
								'woocommerce'
							) }
						</Button>
					</p>
				</>
			) }
			{ showPaymentMethods && <PaymentMethodOptions /> }
		</>
	);
};

export default PaymentMethods;
