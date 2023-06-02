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
	useEmitResponse,
	useShallowEqual,
	useStoreCart,
	useStoreNotices,
} from '@woocommerce/base-hooks';
import {
	CURRENT_USER_IS_ADMIN,
	PAYMENT_GATEWAY_SORT_ORDER,
} from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { useEditorContext } from '../../editor';
import { useShippingDataContext } from '../shipping';

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
 * @param  {string}                     noticeContext            Id of the context to append
 *                                                               notices to.
 *
 * @return {boolean} Whether the payment methods have been initialized or not. True when all payment
 *                   methods have been initialized.
 */
const usePaymentMethodRegistration = (
	dispatcher,
	registeredPaymentMethods,
	paymentMethodsSortOrder,
	noticeContext
) => {
	const [ isInitialized, setIsInitialized ] = useState( false );
	const { isEditor } = useEditorContext();
	const { selectedRates, shippingAddress } = useShippingDataContext();
	const selectedShippingMethods = useShallowEqual( selectedRates );
	const paymentMethodsOrder = useShallowEqual( paymentMethodsSortOrder );
	const {
		cartTotals,
		cartNeedsShipping,
		paymentRequirements,
	} = useStoreCart();
	const canPayArgument = useRef( {
		cartTotals,
		cartNeedsShipping,
		shippingAddress,
		selectedShippingMethods,
		paymentRequirements,
	} );
	const { addErrorNotice } = useStoreNotices();

	useEffect( () => {
		canPayArgument.current = {
			cartTotals,
			cartNeedsShipping,
			shippingAddress,
			selectedShippingMethods,
			paymentRequirements,
		};
	}, [
		cartTotals,
		cartNeedsShipping,
		shippingAddress,
		selectedShippingMethods,
		paymentRequirements,
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
				if ( CURRENT_USER_IS_ADMIN || isEditor ) {
					const errorText = sprintf(
						/* translators: %s the id of the payment method being registered (bank transfer, Stripe...) */
						__(
							`There was an error registering the payment method with id '%s': `,
							'woocommerce'
						),
						paymentMethod.paymentMethodId
					);
					addErrorNotice( `${ errorText } ${ e }`, {
						context: noticeContext,
						id: `wc-${ paymentMethod.paymentMethodId }-registration-error`,
					} );
				}
			}
		}

		// Re-dispatch available payment methods to store.
		dispatcher( availablePaymentMethods );

		// Note: some payment methods use the `canMakePayment` callback to initialize / setup.
		// Example: Stripe CC, Stripe Payment Request.
		// That's why we track "is initialised" state here.
		setIsInitialized( true );
	}, [
		addErrorNotice,
		dispatcher,
		isEditor,
		noticeContext,
		paymentMethodsOrder,
		registeredPaymentMethods,
	] );

	// Determine which payment methods are available initially and whenever
	// shipping methods or cart totals change.
	// Some payment methods (e.g. COD) can be disabled for specific shipping methods.
	useEffect( () => {
		refreshCanMakePayments();
	}, [
		refreshCanMakePayments,
		cartTotals,
		selectedShippingMethods,
		paymentRequirements,
	] );

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
	const { noticeContexts } = useEmitResponse();
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
		Array.from( displayOrder ),
		noticeContexts.PAYMENTS
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
	const { noticeContexts } = useEmitResponse();
	return usePaymentMethodRegistration(
		dispatcher,
		expressMethods,
		Object.keys( expressMethods ),
		noticeContexts.EXPRESS_PAYMENTS
	);
};
