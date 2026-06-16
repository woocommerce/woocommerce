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
const cardConfig = settings?.paymentMethodsConfig?.card || {};
const defaultLabel = __( 'Card', 'woocommerce' );
const label =
	decodeEntities( cardConfig?.title || cardConfig?.label || settings?.title || '' ) ||
	defaultLabel;
const isTestMode = Boolean( settings?.testMode );
const testingInstructions =
	cardConfig?.testingInstructions ||
	settings?.testingInstructions ||
	'';
const cardBrandIcons = Array.isArray( cardConfig?.cardBrandIcons )
	? cardConfig.cardBrandIcons
	: [];
const testModeBadgeLabel = __( 'Test Mode', 'woocommerce' );
const ariaLabel = isTestMode ? `${ label } ${ testModeBadgeLabel }` : label;
const testModeBadgeStyle = {
	backgroundColor: '#fff2d7',
	borderRadius: '4px',
	color: '#4d3716',
	display: 'inline-block',
	fontSize: '12px',
	fontWeight: 400,
	lineHeight: '16px',
	marginLeft: '8px',
	padding: '4px 6px',
};

const TestModeBadge = () => {
	if ( ! isTestMode ) {
		return null;
	}

	return (
		<span className="test-mode badge" style={ testModeBadgeStyle }>
			{ testModeBadgeLabel }
		</span>
	);
};

const CardBrandIcons = () => {
	if ( ! cardBrandIcons.length ) {
		return null;
	}

	const visibleIcons = cardBrandIcons.slice( 0, 4 );
	const additionalIconCount = cardBrandIcons.length - visibleIcons.length;

	return (
		<span
			className="wcpay-core-card-brand-icons"
			style={ {
				display: 'inline-flex',
				gap: '4px',
				marginLeft: '8px',
				verticalAlign: 'middle',
			} }
		>
			{ visibleIcons.map( ( icon ) => (
				<img
					key={ icon.id || icon.src }
					src={ icon.src }
					alt={ icon.alt || icon.id || '' }
					width="38"
					height="24"
				/>
			) ) }
			{ additionalIconCount > 0 ? (
				<span className="payment-methods--logos-count">
					{ `+ ${ additionalIconCount }` }
				</span>
			) : null }
		</span>
	);
};

const PaymentMethodIcon = () => (
	<>
		<TestModeBadge />
		<CardBrandIcons />
	</>
);

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

const updateOrderStatusAfterConfirmation = async (
	confirmation,
	intentId,
	shouldSavePaymentMethod = false
) => {
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
	body.append(
		'should_save_payment_method',
		shouldSavePaymentMethod ? 'true' : 'false'
	);
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

const getStripeElementsOptions = () => {
	const amount = Number( settings.cartTotal || 0 );
	const options = {
		mode: amount > 0 && Number.isFinite( amount ) ? 'payment' : 'setup',
		currency: ( settings.currency || 'usd' ).toLowerCase(),
		paymentMethodCreation: 'manual',
		paymentMethodTypes: [ 'card' ],
	};

	if ( options.mode === 'payment' ) {
		options.amount = amount;
	}

	return options;
};

const getStripePaymentElementOptions = () => ( {
	fields: {
		billingDetails: {
			name: 'never',
			email: 'never',
			phone: 'never',
			address: {
				country: 'never',
				line1: 'never',
				line2: 'never',
				city: 'never',
				state: 'never',
				postalCode: 'never',
			},
		},
	},
	wallets: {
		applePay: 'never',
		googlePay: 'never',
		link: 'never',
	},
} );

const getFieldValue = ( selector ) => {
	const field = document.querySelector( selector );
	return field ? field.value : '';
};

const getBillingDetails = () => {
	const firstName = getFieldValue( '#billing-first_name' );
	const lastName = getFieldValue( '#billing-last_name' );
	const name = `${ firstName || '' } ${ lastName || '' }`.trim();

	return {
		name,
		email: getFieldValue( '#email' ),
		phone: getFieldValue( '#billing-phone' ),
		address: {
			city: getFieldValue( '#billing-city' ),
			country: getFieldValue( '#billing-country' ),
			line1: getFieldValue( '#billing-address_1' ),
			line2: getFieldValue( '#billing-address_2' ),
			postal_code: getFieldValue( '#billing-postcode' )?.trim(),
			state: getFieldValue( '#billing-state' ),
		},
	};
};

const WooPaymentsContent = ( {
	eventRegistration,
	emitResponse,
	shouldSavePayment,
} ) => {
	const { onPaymentSetup, onCheckoutSuccess } = eventRegistration || {};
	const elementContainer = useRef( null );
	const stripe = useRef( null );
	const elements = useRef( null );
	const paymentElement = useRef( null );
	const emitResponseRef = useRef( emitResponse );
	const shouldSavePaymentRef = useRef( shouldSavePayment );

	emitResponseRef.current = emitResponse;
	shouldSavePaymentRef.current = shouldSavePayment;

	useEffect( () => {
		const copyTestNumber = ( event ) => {
			if ( ! ( event.target instanceof window.Element ) ) {
				return;
			}

			const button = event.target.closest(
				'.js-woopayments-copy-test-number'
			);
			const testNumber = button?.textContent?.trim();

			if ( ! button || ! testNumber || ! window.navigator?.clipboard ) {
				return;
			}

			window.navigator.clipboard.writeText( testNumber );
		};

		document.addEventListener( 'click', copyTestNumber );

		return () => {
			document.removeEventListener( 'click', copyTestNumber );
		};
	}, [] );

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

		elements.current = stripe.current.elements(
			getStripeElementsOptions()
		);
		paymentElement.current = elements.current.create(
			'payment',
			getStripePaymentElementOptions()
		);
		paymentElement.current.mount( elementContainer.current );
	}, [] );

	useEffect( () => {
		if ( ! onPaymentSetup ) {
			return undefined;
		}

		const unsubscribe = onPaymentSetup( async () => {
			const paymentMethodData = {
				'wcpay-payment-method': '',
				'wcpay-payment-method-error-code': '',
				'wcpay-payment-method-error-message': '',
				'wcpay-fingerprint': '',
			};

			if ( stripe.current && elements.current ) {
				if ( typeof elements.current.submit === 'function' ) {
					const submitResult = await elements.current.submit();
					if ( submitResult?.error ) {
						paymentMethodData[ 'wcpay-payment-method-error-code' ] =
							submitResult.error.code || '';
						paymentMethodData[
							'wcpay-payment-method-error-message'
						] = submitResult.error.message || '';

						return getErrorResponse(
							emitResponseRef.current,
							submitResult.error.message ||
								__(
									'There was a problem validating your payment details.',
									'woocommerce'
								)
						);
					}
				}

				const result = await stripe.current.createPaymentMethod( {
					elements: elements.current,
					params: {
						billing_details: getBillingDetails(),
					},
				} );

				if ( result.error ) {
					paymentMethodData[ 'wcpay-payment-method-error-code' ] =
						result.error.code || '';
					paymentMethodData[ 'wcpay-payment-method-error-message' ] =
						result.error.message || '';

					return getErrorResponse(
						emitResponseRef.current,
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

			return getSuccessResponse(
				emitResponseRef.current,
				paymentMethodData
			);
		} );

		return typeof unsubscribe === 'function' ? unsubscribe : undefined;
	}, [ onPaymentSetup ] );

	useEffect( () => {
		if ( ! onCheckoutSuccess ) {
			return undefined;
		}

		const unsubscribe = onCheckoutSuccess( async ( response ) => {
			const currentEmitResponse = emitResponseRef.current;
			const confirmation = parseConfirmationRedirect( response );
			if ( ! confirmation ) {
				return getSuccessResponse( currentEmitResponse, {} );
			}

			stripe.current = stripe.current || createStripe();

			if ( ! stripe.current ) {
				return getSuccessResponse( currentEmitResponse, {} );
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
				result = await stripe.current.handleNextAction( {
					clientSecret: confirmation.clientSecret,
				} );
			}

			if ( result.error ) {
				return getErrorResponse(
					currentEmitResponse,
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
				intentId,
				Boolean( shouldSavePaymentRef.current )
			);

			return getSuccessResponse( currentEmitResponse, {}, redirectUrl );
		} );

		return typeof unsubscribe === 'function' ? unsubscribe : undefined;
	}, [ onCheckoutSuccess ] );

	return (
		<>
			{ isTestMode && testingInstructions ? (
				<p
					className="wcpay-core-test-mode-instructions"
					dangerouslySetInnerHTML={ {
						__html: testingInstructions,
					} }
				/>
			) : null }
			<div
				id="wcpay-core-blocks-payment-element"
				className="wcpay-core-blocks-payment-element"
				ref={ elementContainer }
				aria-live="polite"
			/>
		</>
	);
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } icon={ <PaymentMethodIcon /> } />;
};

export const getWooPaymentsPaymentMethod = () => ( {
	name: PAYMENT_METHOD_NAME,
	label: <Label />,
	content: <WooPaymentsContent />,
	edit: <WooPaymentsContent />,
	canMakePayment: () => Boolean( settings.isCoreNativeCheckoutAvailable ),
	ariaLabel,
	supports: {
		features: settings?.supports ?? [],
		showSavedCards: true,
		showSaveOption: true,
	},
} );

const registerWooPayments = () => {
	const paymentMethod = getWooPaymentsPaymentMethod();
	registerPaymentMethod( paymentMethod );

	return paymentMethod;
};

registerWooPayments();

export default registerWooPayments;
