/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
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
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';

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
	const { isEditor } = useEditorContext();
	const [ isInitialized, setIsInitialized ] = useState( false );

	/**
	 * @type {Object} initializedMethodsDefault Used to hold payment methods that have been
	 *                                          initialized.
	 */
	const [
		initializedPaymentMethods,
		setInitializedPaymentMethods,
	] = useState( {} );
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

	const setInitializedPaymentMethod = ( paymentMethod ) => {
		setInitializedPaymentMethods( ( paymentMethods ) => ( {
			...paymentMethods,
			[ paymentMethod.name ]: paymentMethod,
		} ) );
	};

	useEffect( () => {
		canPayArgument.current = {
			cartTotals,
			cartNeedsShipping,
			shippingAddress,
		};
	}, [ cartTotals, cartNeedsShipping, shippingAddress ] );

	// Handles initialization of all payment methods.
	// Note: registeredPaymentMethods is not a dependency because this will not
	// change in the life of the hook, it comes from an externally set value.
	useEffect( () => {
		// if all payment methods are initialized then bail.
		if ( isInitialized ) {
			return;
		}
		const updatePaymentMethod = ( current, canPay = true ) => {
			if ( canPay ) {
				setInitializedPaymentMethod( current );
			}
			// update the initialized count
			countPaymentMethodsInitializing.current--;
			// if count remaining less than 1, then set initialized.
			if ( countPaymentMethodsInitializing.current < 1 ) {
				setIsInitialized( true );
			}
		};
		// loop through payment methods and see what the state is
		for ( const paymentMethodName in registeredPaymentMethods ) {
			const current = registeredPaymentMethods[ paymentMethodName ];
			// if in editor context then we bypass can pay check.
			if ( isEditor ) {
				updatePaymentMethod( current );
			} else {
				Promise.resolve(
					current.canMakePayment( canPayArgument.current )
				)
					.then( ( canPay ) => {
						if ( canPay.error ) {
							throw new Error( canPay.error.message );
						} else {
							updatePaymentMethod( current, canPay );
						}
					} )
					.catch( ( error ) => {
						updatePaymentMethod( current, false );
						if ( CURRENT_USER_IS_ADMIN ) {
							throw new Error(
								sprintf(
									__(
										// translators: %s is the error method returned by the payment method.
										'Problem with payment method initialization: %s',
										'woo-gutenberg-products-block'
									),
									error.message
								)
							);
						}
					} );
			}
		}
	}, [ isInitialized, isEditor ] );

	// once all payment methods have been initialized, resort to be in the same
	// order as registered and then set via the dispatcher.
	// Note: registeredPaymentMethods is not a dependency because this will not
	// change in the life of the hook, it comes from an externally set value.
	useEffect( () => {
		if ( isInitialized ) {
			const reSortByRegisteredOrder = () => {
				const newSet = {};
				for ( const paymentMethodName in registeredPaymentMethods ) {
					if ( initializedPaymentMethods[ paymentMethodName ] ) {
						newSet[ paymentMethodName ] =
							initializedPaymentMethods[ paymentMethodName ];
					}
				}
				return newSet;
			};
			dispatcher( reSortByRegisteredOrder() );
		}
	}, [ isInitialized, initializedPaymentMethods ] );

	return isInitialized;
};

export const usePaymentMethods = ( dispatcher ) =>
	usePaymentMethodRegistration( dispatcher, getPaymentMethods() );
export const useExpressPaymentMethods = ( dispatcher ) =>
	usePaymentMethodRegistration( dispatcher, getExpressPaymentMethods() );
