/**
 * External dependencies
 */
import {
	getPaymentMethods,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';
import { useState, useEffect, useRef } from '@wordpress/element';

const usePaymentMethodState = ( registeredPaymentMethods ) => {
	const [ paymentMethods, setPaymentMethods ] = useState( [] );
	const [ isInitialized, setIsInitialized ] = useState( false );
	const countPaymentMethodsInitializing = useRef(
		Object.keys( registeredPaymentMethods ).length
	);

	useEffect( () => {
		// if all payment methods are initialized then bail.
		if ( isInitialized ) {
			return;
		}
		// loop through payment methods and see what the state is
		for ( const paymentMethodId in registeredPaymentMethods ) {
			const current = registeredPaymentMethods[ paymentMethodId ];
			current.canMakePayment
				.then( ( canPay ) => {
					if ( canPay ) {
						setPaymentMethods( ( previousPaymentMethods ) => {
							return {
								...previousPaymentMethods,
								[ current.id ]: current,
							};
						} );
					}
					// update the initialized count
					countPaymentMethodsInitializing.current--;
					// if count remaining less than 1, then set initialized.
					if ( countPaymentMethodsInitializing.current < 1 ) {
						setIsInitialized( true );
					}
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
	}, [ isInitialized ] );

	return { paymentMethods, isInitialized };
};

export const usePaymentMethods = () =>
	usePaymentMethodState( getPaymentMethods() );
export const useExpressPaymentMethods = () =>
	usePaymentMethodState( getExpressPaymentMethods() );
