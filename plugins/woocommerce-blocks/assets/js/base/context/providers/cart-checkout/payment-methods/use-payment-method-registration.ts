/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	getPaymentMethods,
	getExpressPaymentMethods,
} from '@woocommerce/blocks-registry';
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { useShallowEqual } from '@woocommerce/base-hooks';
import { CURRENT_USER_IS_ADMIN, getSetting } from '@woocommerce/settings';
import type {
	PaymentMethods,
	ExpressPaymentMethods,
	PaymentMethodConfigInstance,
	ExpressPaymentMethodConfigInstance,
} from '@woocommerce/type-defs/payments';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useEditorContext } from '../../editor-context';
import { useShippingDataContext } from '../shipping';
import { useCustomerDataContext } from '../customer';
import { useStoreCart } from '../../../hooks/cart/use-store-cart';
import { useStoreNotices } from '../../../hooks/use-store-notices';
import { useEmitResponse } from '../../../hooks/use-emit-response';
import type { PaymentMethodsDispatcherType } from './types';

/**
 * This hook handles initializing registered payment methods and exposing all
 * registered payment methods that can be used in the current environment (via
 * the payment method's `canMakePayment` property).
 *
 * @param  {function(Object):undefined} dispatcher               A dispatcher for setting registered payment methods to an external state.
 * @param  {Object}                     registeredPaymentMethods Registered payment methods to process.
 * @param  {Array}                      paymentMethodsSortOrder  Array of payment method names to sort by. This should match keys of registeredPaymentMethods.
 * @param  {string}                     noticeContext            Id of the context to append notices to.
 *
 * @return {boolean} Whether the payment methods have been initialized or not. True when all payment methods have been initialized.
 */
const usePaymentMethodRegistration = (
	dispatcher: PaymentMethodsDispatcherType,
	registeredPaymentMethods: PaymentMethods | ExpressPaymentMethods,
	paymentMethodsSortOrder: string[],
	noticeContext: string
) => {
	const [ isInitialized, setIsInitialized ] = useState( false );
	const { isEditor } = useEditorContext();
	const { selectedRates } = useShippingDataContext();
	const { billingData, shippingAddress } = useCustomerDataContext();
	const selectedShippingMethods = useShallowEqual( selectedRates );
	const paymentMethodsOrder = useShallowEqual( paymentMethodsSortOrder );
	const cart = useStoreCart();
	const { cartTotals, cartNeedsShipping, paymentRequirements } = cart;
	const canPayArgument = useRef( {
		cart,
		cartTotals,
		cartNeedsShipping,
		billingData,
		shippingAddress,
		selectedShippingMethods,
		paymentRequirements,
	} );
	const { addErrorNotice } = useStoreNotices();

	useEffect( () => {
		canPayArgument.current = {
			cart,
			cartTotals,
			cartNeedsShipping,
			billingData,
			shippingAddress,
			selectedShippingMethods,
			paymentRequirements,
		};
	}, [
		cart,
		cartTotals,
		cartNeedsShipping,
		billingData,
		shippingAddress,
		selectedShippingMethods,
		paymentRequirements,
	] );

	const refreshCanMakePayments = useCallback( async () => {
		let availablePaymentMethods = {};

		const addAvailablePaymentMethod = (
			paymentMethod:
				| PaymentMethodConfigInstance
				| ExpressPaymentMethodConfigInstance
		) => {
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

			// See if payment method should be available. This always evaluates to true in the editor context.
			try {
				const canPay = isEditor
					? true
					: await Promise.resolve(
							paymentMethod.canMakePayment(
								canPayArgument.current
							)
					  );

				if ( canPay ) {
					if (
						typeof canPay === 'object' &&
						canPay !== null &&
						canPay.error
					) {
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
							'woo-gutenberg-products-block'
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
		// That's why we track "is initialized" state here.
		setIsInitialized( true );
	}, [
		addErrorNotice,
		dispatcher,
		isEditor,
		noticeContext,
		paymentMethodsOrder,
		registeredPaymentMethods,
	] );

	const [ debouncedRefreshCanMakePayments ] = useDebouncedCallback(
		refreshCanMakePayments,
		500
	);

	// Determine which payment methods are available initially and whenever
	// shipping methods, cart or the billing data change.
	// Some payment methods (e.g. COD) can be disabled for specific shipping methods.
	useEffect( () => {
		debouncedRefreshCanMakePayments();
	}, [
		debouncedRefreshCanMakePayments,
		cart,
		selectedShippingMethods,
		billingData,
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
export const usePaymentMethods = (
	dispatcher: PaymentMethodsDispatcherType
): boolean => {
	const standardMethods: PaymentMethods = getPaymentMethods() as PaymentMethods;
	const { noticeContexts } = useEmitResponse();
	// Ensure all methods are present in order.
	// Some payment methods may not be present in paymentGatewaySortOrder if they
	// depend on state, e.g. COD can depend on shipping method.
	const displayOrder = new Set( [
		...( getSetting( 'paymentGatewaySortOrder', [] ) as [  ] ),
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
export const useExpressPaymentMethods = (
	dispatcher: PaymentMethodsDispatcherType
): boolean => {
	const expressMethods: ExpressPaymentMethods = getExpressPaymentMethods() as ExpressPaymentMethods;
	const { noticeContexts } = useEmitResponse();
	return usePaymentMethodRegistration(
		dispatcher,
		expressMethods,
		Object.keys( expressMethods ),
		noticeContexts.EXPRESS_PAYMENTS
	);
};
