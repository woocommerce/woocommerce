/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	getPaymentMethods,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import {
	useEditorContext,
	useShippingDataContext,
} from '@woocommerce/base-context';
import { useStoreCart } from '@woocommerce/base-hooks';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';

/**
 * If there was an error registering a payment method, alert the admin.
 *
 * @param {Object} error Error object.
 */
const handleRegistrationError = ( error ) => {
	if ( CURRENT_USER_IS_ADMIN ) {
		throw new Error(
			sprintf(
				__(
					// translators: %s is the error method returned by the payment method.
					'Problem with payment method initialization: %s',
					'woocommerce'
				),
				error.message
			)
		);
	}
};

/**
 * This hook handles initializing registered payment methods and exposing all
 * registered payment methods that can be used in the current environment (via
 * the payment method's `canMakePayment` property).
 *
 * @param  {function(Object):undefined} dispatcher               A dispatcher for setting registered
 *                                                               payment methods to an external
 *                                                               state.
 * @param  {Object}                     registeredPaymentMethods Registered payment methods to
 *                                                               process.
 *
 * @return {boolean} Whether the payment methods have been initialized or not. True when all payment
 *                   methods have been initialized.
 */
const usePaymentMethodRegistration = (
	dispatcher,
	registeredPaymentMethods
) => {
	const [ isInitialized, setIsInitialized ] = useState( false );
	const { isEditor } = useEditorContext();
	const { shippingAddress } = useShippingDataContext();
	const { cartTotals, cartNeedsShipping } = useStoreCart();
	const canPayArgument = useRef( {
		cartTotals,
		cartNeedsShipping,
		shippingAddress,
	} );

	useEffect( () => {
		canPayArgument.current = {
			cartTotals,
			cartNeedsShipping,
			shippingAddress,
		};
	}, [ cartTotals, cartNeedsShipping, shippingAddress ] );

	const resolveCanMakePayments = useCallback( async () => {
		let initializedPaymentMethods = {},
			canPay;
		const setInitializedPaymentMethods = ( paymentMethod ) => {
			initializedPaymentMethods = {
				...initializedPaymentMethods,
				[ paymentMethod.name ]: paymentMethod,
			};
		};
		for ( const paymentMethodName in registeredPaymentMethods ) {
			const current = registeredPaymentMethods[ paymentMethodName ];

			if ( isEditor ) {
				setInitializedPaymentMethods( current );
				continue;
			}

			try {
				canPay = await Promise.resolve(
					current.canMakePayment( canPayArgument.current )
				);
				if ( canPay ) {
					if ( canPay.error ) {
						throw new Error( canPay.error.message );
					}
					setInitializedPaymentMethods( current );
				}
			} catch ( e ) {
				handleRegistrationError( e );
			}
		}
		// all payment methods have been initialized so dispatch and set
		dispatcher( initializedPaymentMethods );
		setIsInitialized( true );
	}, [ dispatcher, isEditor, registeredPaymentMethods ] );

	// if not initialized invoke the callback to kick off resolving the payments.
	useEffect( () => {
		if ( ! isInitialized ) {
			resolveCanMakePayments();
		}
	}, [ resolveCanMakePayments, isInitialized ] );

	return isInitialized;
};

export const usePaymentMethods = ( dispatcher ) =>
	usePaymentMethodRegistration( dispatcher, getPaymentMethods() );
export const useExpressPaymentMethods = ( dispatcher ) =>
	usePaymentMethodRegistration( dispatcher, getExpressPaymentMethods() );
