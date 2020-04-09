/**
 * External dependencies
 */
import {
	getPaymentMethods,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	useEditorContext,
	useShippingDataContext,
} from '@woocommerce/base-context';
import { useStoreCart } from '@woocommerce/base-hooks';

const usePaymentMethodRegistration = (
	dispatcher,
	registeredPaymentMethods
) => {
	const { isEditor } = useEditorContext();
	const [ isInitialized, setIsInitialized ] = useState( false );
	const { shippingAddress } = useShippingDataContext();
	const { cartTotals, cartNeedsShipping } = useStoreCart();
	const canPayArgument = useRef( {
		cartTotals,
		cartNeedsShipping,
		shippingAddress,
	} );
	const countPaymentMethodsInitializing = useRef(
		Object.keys( registeredPaymentMethods ).length
	);

	useEffect( () => {
		canPayArgument.current = {
			cartTotals,
			cartNeedsShipping,
			shippingAddress,
		};
	}, [ cartTotals, cartNeedsShipping, shippingAddress ] );

	useEffect( () => {
		// if all payment methods are initialized then bail.
		if ( isInitialized ) {
			return;
		}
		const updatePaymentMethods = ( current, canPay = true ) => {
			if ( canPay ) {
				dispatcher( current );
			}
			// update the initialized count
			countPaymentMethodsInitializing.current--;
			// if count remaining less than 1, then set initialized.
			if ( countPaymentMethodsInitializing.current < 1 ) {
				setIsInitialized( true );
			}
		};
		// loop through payment methods and see what the state is
		for ( const paymentMethodId in registeredPaymentMethods ) {
			const current = registeredPaymentMethods[ paymentMethodId ];
			// if in editor context then we bypass can pay check.
			if ( isEditor ) {
				updatePaymentMethods( current );
			} else {
				Promise.resolve(
					current.canMakePayment( canPayArgument.current )
				)
					.then( ( canPay ) => {
						updatePaymentMethods( current, canPay );
					} )
					.catch( ( error ) => {
						// @todo, would be a good place to use the checkout error
						// hooks here? Or maybe throw and catch by error boundary?
						throw new Error(
							'Problem with payment method initialization' +
								( error.message || '' )
						);
					} );
			}
		}
	}, [ isInitialized, isEditor ] );

	return isInitialized;
};

export const usePaymentMethods = ( dispatcher ) =>
	usePaymentMethodRegistration( dispatcher, getPaymentMethods() );
export const useExpressPaymentMethods = ( dispatcher ) =>
	usePaymentMethodRegistration( dispatcher, getExpressPaymentMethods() );
