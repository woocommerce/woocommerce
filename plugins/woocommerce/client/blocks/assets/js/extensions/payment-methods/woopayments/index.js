/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getPaymentMethodData } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

const PAYMENT_METHOD_NAME = 'woocommerce_payments';
const settings = getPaymentMethodData( PAYMENT_METHOD_NAME, {} );
const defaultLabel = __( 'WooPayments', 'woocommerce' );
const label = decodeEntities( settings?.title || '' ) || defaultLabel;

const getSuccessResponse = ( emitResponse, paymentMethodData ) => ( {
	type: emitResponse.responseTypes.SUCCESS,
	meta: {
		paymentMethodData,
	},
} );

const getErrorResponse = ( emitResponse, message ) => ( {
	type: emitResponse.responseTypes.ERROR,
	message,
	messageContext: emitResponse.noticeContexts.PAYMENTS,
} );

const getConfirmationRedirect = ( value ) => {
	if ( typeof value === 'string' && value.includes( '#wcpay-confirm-pi:' ) ) {
		return value;
	}

	if ( ! value || typeof value !== 'object' ) {
		return '';
	}

	return Object.values( value ).reduce( ( redirect, child ) => {
		return redirect || getConfirmationRedirect( child );
	}, '' );
};

const updateOrderStatusAfterConfirmation = async ( intentId ) => {
	if (
		! settings.usesLegacyOrderStatusBridge ||
		! settings.updateOrderStatusNonce ||
		! settings.ajaxUrl ||
		! intentId ||
		! window.fetch
	) {
		return;
	}

	const body = new window.URLSearchParams();
	body.append( 'action', 'wcpay_update_order_status' );
	body.append( '_ajax_nonce', settings.updateOrderStatusNonce );
	body.append( 'intent_id', intentId );

	await window.fetch( settings.ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body,
	} );
};

const createStripe = () => {
	if ( ! settings.publishableKey || ! window.Stripe ) {
		return null;
	}

	return window.Stripe( settings.publishableKey, {
		locale: settings.locale || 'auto',
		stripeAccount: settings.accountId || undefined,
	} );
};

const WooPaymentsContent = ( { eventRegistration, emitResponse } ) => {
	const elementContainer = useRef( null );
	const stripe = useRef( null );
	const elements = useRef( null );
	const paymentElement = useRef( null );

	useEffect( () => {
		if (
			paymentElement.current ||
			! elementContainer.current ||
			! settings.isCoreNativeCheckoutAvailable
		) {
			return;
		}

		stripe.current = createStripe();
		if ( ! stripe.current ) {
			return;
		}

		elements.current = stripe.current.elements( {
			mode: 'payment',
			amount: settings.cartTotal || 0,
			currency: ( settings.currency || 'usd' ).toLowerCase(),
			paymentMethodTypes: [ 'card' ],
		} );
		paymentElement.current = elements.current.create( 'payment' );
		paymentElement.current.mount( elementContainer.current );
	}, [] );

	useEffect( () => {
		if ( ! eventRegistration?.onPaymentSetup ) {
			return undefined;
		}

		const unsubscribe = eventRegistration.onPaymentSetup( async () => {
			const paymentMethodData = {
				'wcpay-payment-method': '',
				'wcpay-payment-method-error-code': '',
				'wcpay-payment-method-error-message': '',
				'wcpay-fingerprint': '',
			};

			if ( stripe.current && elements.current ) {
				const result = await stripe.current.createPaymentMethod( {
					elements: elements.current,
				} );

				if ( result.error ) {
					paymentMethodData[ 'wcpay-payment-method-error-code' ] =
						result.error.code || '';
					paymentMethodData[ 'wcpay-payment-method-error-message' ] =
						result.error.message || '';

					return getErrorResponse(
						emitResponse,
						result.error.message ||
							__(
								'There was a problem validating your payment details.',
								'woocommerce'
							)
					);
				}

				if ( result.paymentMethod ) {
					paymentMethodData[ 'wcpay-payment-method' ] =
						result.paymentMethod.id || '';
					paymentMethodData[ 'wcpay-fingerprint' ] =
						result.paymentMethod.card?.fingerprint || '';
				}
			}

			return getSuccessResponse( emitResponse, paymentMethodData );
		} );

		return typeof unsubscribe === 'function' ? unsubscribe : undefined;
	}, [ emitResponse, eventRegistration ] );

	useEffect( () => {
		if ( ! eventRegistration?.onCheckoutSuccess ) {
			return undefined;
		}

		const unsubscribe = eventRegistration.onCheckoutSuccess(
			async ( response ) => {
				const redirect = getConfirmationRedirect( response );
				const prefix = '#wcpay-confirm-pi:';
				if ( ! redirect || ! redirect.includes( prefix ) ) {
					return getSuccessResponse( emitResponse, {} );
				}

				const clientSecret = decodeURIComponent(
					redirect.substring(
						redirect.indexOf( prefix ) + prefix.length
					)
				);
				const intentId = clientSecret.split( '_secret_' )[ 0 ];
				stripe.current = stripe.current || createStripe();

				if ( ! stripe.current ) {
					return getSuccessResponse( emitResponse, {} );
				}

				const result = await stripe.current.confirmPayment( {
					clientSecret,
					confirmParams: {
						return_url: window.location.href.split( '#' )[ 0 ],
					},
					redirect: 'if_required',
				} );

				if ( result.error ) {
					return getErrorResponse(
						emitResponse,
						result.error.message ||
							__(
								'There was a problem confirming your payment.',
								'woocommerce'
							)
					);
				}

				await updateOrderStatusAfterConfirmation( intentId );

				return getSuccessResponse( emitResponse, {} );
			}
		);

		return typeof unsubscribe === 'function' ? unsubscribe : undefined;
	}, [ emitResponse, eventRegistration ] );

	return (
		<div
			id="wcpay-core-blocks-payment-element"
			className="wcpay-core-blocks-payment-element"
			ref={ elementContainer }
			aria-live="polite"
		/>
	);
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

export const getWooPaymentsPaymentMethod = () => ( {
	name: PAYMENT_METHOD_NAME,
	label: <Label />,
	content: <WooPaymentsContent />,
	edit: <WooPaymentsContent />,
	canMakePayment: () => Boolean( settings.isCoreNativeCheckoutAvailable ),
	ariaLabel: label,
	supports: {
		features: settings?.supports ?? [],
	},
} );

const registerWooPayments = () => {
	const paymentMethod = getWooPaymentsPaymentMethod();
	registerPaymentMethod( paymentMethod );

	return paymentMethod;
};

registerWooPayments();

export default registerWooPayments;
