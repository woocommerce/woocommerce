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
import { useStoreCart, useShallowEqual } from '@woocommerce/base-hooks';
import {
	CURRENT_USER_IS_ADMIN,
	PAYMENT_GATEWAY_SORT_ORDER,
} from '@woocommerce/block-settings';

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
 * @param  {Array}                      paymentMethodsSortOrder  Array of payment method names to
 *                                                               sort by. This should match keys of
 *                                                               registeredPaymentMethods.
 *
 * @return {boolean} Whether the payment methods have been initialized or not. True when all payment
 *                   methods have been initialized.
 */
const usePaymentMethodRegistration = (
	dispatcher,
	registeredPaymentMethods,
	paymentMethodsSortOrder
) => {
	const [ isInitialized, setIsInitialized ] = useState( false );
	const { isEditor } = useEditorContext();
	const { selectedRates, shippingAddress } = useShippingDataContext();
	const selectedShippingMethods = useShallowEqual( selectedRates );
	const paymentMethodsOrder = useShallowEqual( paymentMethodsSortOrder );
	const { cartTotals, cartNeedsShipping } = useStoreCart();
	const canPayArgument = useRef( {
		cartTotals,
		cartNeedsShipping,
		shippingAddress,
		selectedShippingMethods,
	} );

	useEffect( () => {
		canPayArgument.current = {
			cartTotals,
			cartNeedsShipping,
			shippingAddress,
			selectedShippingMethods,
		};
	}, [
		cartTotals,
		cartNeedsShipping,
		shippingAddress,
		selectedShippingMethods,
	] );

	const refreshCanMakePayments = useCallback( async () => {
		let availablePaymentMethods = {};
		const addAvailablePaymentMethod = ( paymentMethod ) => {
			availablePaymentMethods = {
				...availablePaymentMethods,
				[ paymentMethod.name ]: paymentMethod,
			};
		};

		for ( let i = 0; i < paymentMethodsOrder.length; i++ ) {
			const paymentMethodName = paymentMethodsOrder[ i ];
			const paymentMethod = registeredPaymentMethods[ paymentMethodName ];
			if ( ! paymentMethod ) {
				continue;
			}

			// In editor, shortcut so all payment methods show as available.
			if ( isEditor ) {
				addAvailablePaymentMethod( paymentMethod );
				continue;
			}

			// In front end, ask payment method if it should be available.
			try {
				const canPay = await Promise.resolve(
					paymentMethod.canMakePayment( canPayArgument.current )
				);
				if ( canPay ) {
					if ( canPay.error ) {
						throw new Error( canPay.error.message );
					}
					addAvailablePaymentMethod( paymentMethod );
				}
			} catch ( e ) {
				// If user is admin, show payment `canMakePayment` errors as a notice.
				handleRegistrationError( e );
			}
		}

		// Re-dispatch available payment methods to store.
		dispatcher( availablePaymentMethods );

		// Note: some payment methods use the `canMakePayment` callback to initialize / setup.
		// Example: Stripe CC, Stripe Payment Request.
		// That's why we track "is initialised" state here.
		setIsInitialized( true );
	}, [
		dispatcher,
		isEditor,
		registeredPaymentMethods,
		paymentMethodsOrder,
	] );

	// Determine which payment methods are available initially and whenever
	// shipping methods change.
	// Some payment methods (e.g. COD) can be disabled for specific shipping methods.
	useEffect( () => {
		refreshCanMakePayments();
	}, [ refreshCanMakePayments, selectedShippingMethods ] );

	return isInitialized;
};

/**
 * Custom hook for setting up payment methods (standard, non-express).
 *
 * @param  {function(Object):undefined} dispatcher
 *
 * @return {boolean} True when standard payment methods have been initialized.
 */
export const usePaymentMethods = ( dispatcher ) => {
	const standardMethods = getPaymentMethods();
	// Ensure all methods are present in order.
	// Some payment methods may not be present in PAYMENT_GATEWAY_SORT_ORDER if they
	// depend on state, e.g. COD can depend on shipping method.
	const displayOrder = new Set( [
		...PAYMENT_GATEWAY_SORT_ORDER,
		...Object.keys( standardMethods ),
	] );
	return usePaymentMethodRegistration(
		dispatcher,
		standardMethods,
		Array.from( displayOrder )
	);
};

/**
 * Custom hook for setting up express payment methods.
 *
 * @param  {function(Object):undefined} dispatcher
 *
 * @return {boolean} True when express payment methods have been initialized.
 */
export const useExpressPaymentMethods = ( dispatcher ) => {
	const expressMethods = getExpressPaymentMethods();
	return usePaymentMethodRegistration(
		dispatcher,
		expressMethods,
		Object.keys( expressMethods )
	);
};
