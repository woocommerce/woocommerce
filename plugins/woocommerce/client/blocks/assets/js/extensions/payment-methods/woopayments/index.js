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

const getSuccessResponse = (
	emitResponse,
	paymentMethodData,
	redirectUrl = ''
) => ( {
	type: emitResponse.responseTypes.SUCCESS,
	...( redirectUrl ? { redirectUrl } : {} ),
	meta: {
		paymentMethodData,
	},
} );

const getErrorResponse = ( emitResponse, message ) => ( {
	type: emitResponse.responseTypes.ERROR,
	message,
	messageContext: emitResponse.noticeContexts.PAYMENTS,
} );

const parseConfirmationRedirect = ( value ) => {
	if ( typeof value === 'string' ) {
		const match = value.match(
			/#wcpay-confirm-(pi|si):([^:]+):([^:]+):([^:]+)(?::(.+))?$/
		);

		if ( ! match ) {
			return null;
		}

		const clientSecret = decodeURIComponent( match[ 3 ] );

		return {
			type: match[ 1 ],
			orderId: decodeURIComponent( match[ 2 ] ),
			clientSecret,
			nonce: decodeURIComponent( match[ 4 ] ),
			confirmationToken: match[ 5 ]
				? decodeURIComponent( match[ 5 ] )
				: '',
			intentId: clientSecret.split( '_secret_' )[ 0 ],
		};
	}

	if ( ! value || typeof value !== 'object' ) {
		return null;
	}

	return Object.values( value ).reduce( ( confirmation, child ) => {
		return confirmation || parseConfirmationRedirect( child );
	}, null );
};

const updateOrderStatusAfterConfirmation = async ( confirmation, intentId ) => {
	if (
		! settings.ajaxUrl ||
		! confirmation?.orderId ||
		! confirmation?.nonce ||
		! intentId ||
		! window.fetch
	) {
		return;
	}

	const body = new window.URLSearchParams();
	body.append( 'action', 'update_order_status' );
	body.append( 'order_id', confirmation.orderId );
	body.append( '_ajax_nonce', confirmation.nonce );
	body.append( 'intent_id', intentId );
	body.append( 'should_save_payment_method', 'false' );
	body.append( 'is_changing_payment', 'false' );

	const response = await window.fetch( settings.ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body,
	} );

	if ( ! response || typeof response.json !== 'function' ) {
		return;
	}

	const result = await response.json();
	if ( result?.error?.message ) {
		throw new Error( result.error.message );
	}

	return result?.return_url || '';
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
				const confirmation = parseConfirmationRedirect( response );
				if ( ! confirmation ) {
					return getSuccessResponse( emitResponse, {} );
				}

				stripe.current = stripe.current || createStripe();

				if ( ! stripe.current ) {
					return getSuccessResponse( emitResponse, {} );
				}

				let result;

				if ( confirmation.type === 'si' ) {
					result = confirmation.confirmationToken
						? await stripe.current.confirmSetup( {
								clientSecret: confirmation.clientSecret,
								confirmParams: {
									confirmation_token:
										confirmation.confirmationToken,
								},
								redirect: 'if_required',
						  } )
						: await stripe.current.handleNextAction( {
								clientSecret: confirmation.clientSecret,
						  } );
				} else {
					result = await stripe.current.confirmPayment( {
						clientSecret: confirmation.clientSecret,
						confirmParams: {
							return_url: window.location.href.split( '#' )[ 0 ],
						},
						redirect: 'if_required',
					} );
				}

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

				const intentId =
					result.paymentIntent?.id ||
					result.setupIntent?.id ||
					result.error?.payment_intent?.id ||
					result.error?.setup_intent?.id ||
					confirmation.intentId;

				const redirectUrl = await updateOrderStatusAfterConfirmation(
					confirmation,
					intentId
				);

				return getSuccessResponse( emitResponse, {}, redirectUrl );
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
