/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getPaymentMethodData } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { createRoot, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	getBlocksCheckoutAppearance,
	getFontRulesFromPage,
} from './upe-styles';
import { recordWooPaymentsUserEvent } from './tracks';

const PAYMENT_METHOD_NAME = 'woocommerce_payments';
const settings = getPaymentMethodData( PAYMENT_METHOD_NAME, {} );
const cardConfig = settings?.paymentMethodsConfig?.card || {};
const defaultLabel = __( 'Card', 'woocommerce' );
const label =
	decodeEntities(
		cardConfig?.title || cardConfig?.label || settings?.title || ''
	) || defaultLabel;
const isTestMode = Boolean( settings?.testMode );
const testingInstructions =
	cardConfig?.testingInstructions || settings?.testingInstructions || '';
const cardBrandIcons = Array.isArray( cardConfig?.cardBrandIcons )
	? cardConfig.cardBrandIcons
	: [];
const testModeBadgeLabel = __( 'Test Mode', 'woocommerce' );
const ariaLabel = isTestMode ? `${ label } ${ testModeBadgeLabel }` : label;
const saveUserRoots = new WeakMap();
const copyTestNumberSuccessDuration = 2000;

const TestModeBadge = () => {
	if ( ! isTestMode ) {
		return null;
	}

	return <span className="test-mode badge">{ testModeBadgeLabel }</span>;
};

const CardBrandIcons = () => {
	const [ isPopoverOpen, setIsPopoverOpen ] = useState( false );

	if ( ! cardBrandIcons.length ) {
		return null;
	}

	const visibleIcons = cardBrandIcons.slice( 0, 4 );
	const additionalIcons = cardBrandIcons.slice( visibleIcons.length );
	const hasAdditionalIcons = additionalIcons.length > 0;
	const popoverId = 'wcpay-core-payment-methods-popover';
	const togglePopover = () => setIsPopoverOpen( ( isOpen ) => ! isOpen );
	const closePopover = () => setIsPopoverOpen( false );

	return (
		<div className="payment-methods--logos">
			<div
				{ ...( hasAdditionalIcons
					? {
							role: 'button',
							tabIndex: 0,
							'aria-expanded': isPopoverOpen,
							'aria-controls': popoverId,
							onClick: togglePopover,
							onKeyDown: ( event ) => {
								if (
									event.key === 'Enter' ||
									event.key === ' '
								) {
									event.preventDefault();
									togglePopover();
								}
								if ( event.key === 'Escape' ) {
									closePopover();
								}
							},
					  }
					: {} ) }
				data-testid="payment-methods-logos"
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
				{ hasAdditionalIcons ? (
					<div className="payment-methods--logos-count">
						{ `+ ${ additionalIcons.length }` }
					</div>
				) : null }
			</div>
			{ hasAdditionalIcons && isPopoverOpen ? (
				<div
					id={ popoverId }
					className="logo-popover payment-methods--logos-popover"
					role="dialog"
					aria-label={ __(
						'Supported credit card brands',
						'woocommerce'
					) }
				>
					{ additionalIcons.map( ( icon ) => (
						<img
							key={ icon.id || icon.src }
							src={ icon.src }
							alt={ icon.alt || icon.id || '' }
							width="38"
							height="24"
						/>
					) ) }
				</div>
			) : null }
		</div>
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

const shouldUsePlatformStripeForCard = () =>
	Boolean(
		cardConfig?.forceNetworkSavedCards ?? settings.forceNetworkSavedCards
	);

const createStripe = ( forceAccountRequest = false ) => {
	if ( ! settings.publishableKey || ! window.Stripe ) {
		return null;
	}

	const stripeOptions = {
		locale: settings.locale || 'auto',
	};

	if (
		settings.accountId &&
		( forceAccountRequest || ! shouldUsePlatformStripeForCard() )
	) {
		stripeOptions.stripeAccount = settings.accountId;
	}

	return window.Stripe( settings.publishableKey, stripeOptions );
};

const getReusablePaymentMethodTerms = ( value ) => {
	return Object.entries( settings.paymentMethodsConfig || {} ).reduce(
		( terms, [ paymentMethodId, paymentMethodConfig ] ) => {
			if (
				paymentMethodId !== 'link' &&
				paymentMethodConfig?.isReusable
			) {
				terms[ paymentMethodId ] = value;
			}

			return terms;
		},
		{}
	);
};

const isLinkEnabled = () =>
	Boolean(
		settings.paymentMethodsConfig?.link !== undefined &&
			settings.paymentMethodsConfig?.card !== undefined
	);

const getStripePaymentMethodTypes = () =>
	isLinkEnabled() ? [ 'card', 'link' ] : [ 'card' ];

const getStripeElementsOptions = () => {
	const amount = Number( settings.cartTotal || 0 );
	const options = {
		mode: amount > 0 && Number.isFinite( amount ) ? 'payment' : 'setup',
		loader: 'never',
		currency: ( settings.currency || 'usd' ).toLowerCase(),
		paymentMethodCreation: 'manual',
		paymentMethodTypes: getStripePaymentMethodTypes(),
	};

	const appearance = getBlocksCheckoutAppearance(
		settings.stylesCacheVersion
	);
	if ( appearance ) {
		options.appearance = appearance;
	}

	const fonts = getFontRulesFromPage();
	if ( fonts.length ) {
		options.fonts = fonts;
	}

	if ( options.mode === 'payment' ) {
		options.amount = amount;
	}

	return options;
};

const getStripePaymentElementOptions = ( shouldSavePayment = false ) => ( {
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
		link: isLinkEnabled() ? 'auto' : 'never',
	},
	terms: getReusablePaymentMethodTerms(
		shouldSavePayment || settings.cartContainsSubscription
			? 'always'
			: 'never'
	),
} );

const getFieldValue = ( selector ) => {
	const field = document.querySelector( selector );
	return field ? field.value : '';
};

const buildWooPayAjaxUrl = ( endpoint ) => {
	return ( settings.wcAjaxUrl || '/?wc-ajax=%%endpoint%%' ).replace(
		'%%endpoint%%',
		`wcpay_${ endpoint }`
	);
};

const getWooPayViewport = () =>
	`${ window.document.documentElement.clientWidth }x${ window.document.documentElement.clientHeight }`;

const getWooPayInitialPhone = () =>
	getFieldValue( '#billing-phone' ) ||
	getFieldValue( '#phone' ) ||
	getFieldValue( '#shipping-phone' ) ||
	'';

const shouldRenderWooPaySaveUser = () => {
	return Boolean(
		settings.isWooPayEnabled &&
			settings.forceNetworkSavedCards &&
			settings.woopaySessionNonce
	);
};

const persistWooPaySaveUser = async ( isSavingUser, phone ) => {
	if ( ! settings.woopaySessionNonce || ! window.fetch ) {
		return;
	}

	const body = new window.URLSearchParams();
	body.append( '_wpnonce', settings.woopaySessionNonce );
	body.append( 'save_user_in_woopay', isSavingUser ? 'true' : 'false' );
	body.append( 'woopay_source_url', window.location.href );
	body.append( 'woopay_is_blocks', 'true' );
	body.append( 'woopay_viewport', getWooPayViewport() );
	body.append( 'woopay_user_phone_field[full]', phone || '' );

	await window.fetch( buildWooPayAjaxUrl( 'set_woopay_phone_number' ), {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body,
	} );
};

const WooPaySaveUserSection = () => {
	const initialIsSavingUser = useRef(
		Boolean( settings.PRE_CHECK_SAVE_MY_INFO )
	);
	const [ isSavingUser, setIsSavingUser ] = useState(
		initialIsSavingUser.current
	);
	const [ phone, setPhone ] = useState( getWooPayInitialPhone );
	const saveUserLabel =
		settings.woopaySaveUserLabel ||
		__(
			'Securely save my information for 1-click checkout',
			'woocommerce'
		);
	const phoneLabel =
		settings.woopayPhoneLabel || __( 'Mobile phone number', 'woocommerce' );

	useEffect( () => {
		recordWooPaymentsUserEvent(
			settings,
			'checkout_woopay_save_my_info_offered'
		);
		if ( initialIsSavingUser.current ) {
			recordWooPaymentsUserEvent(
				settings,
				'checkout_save_my_info_click',
				{ status: 'checked' }
			);
		}
	}, [] );

	const updateSaveUser = ( checked, nextPhone = phone ) => {
		setIsSavingUser( checked );
		if ( ! checked ) {
			setPhone( '' );
		}
		recordWooPaymentsUserEvent( settings, 'checkout_save_my_info_click', {
			status: checked ? 'checked' : 'unchecked',
		} );
		persistWooPaySaveUser( checked, checked ? nextPhone : '' );
	};

	return (
		<div className="woopay-save-new-user-container">
			<div className="wc-block-components-checkout-step__heading-container">
				<div className="wc-block-components-checkout-step__heading">
					<h2 className="wc-block-components-title wc-block-components-checkout-step__title">
						{ __( 'Save my info', 'woocommerce' ) }
					</h2>
				</div>
			</div>
			<div className="save-details">
				<div className="save-details-header">
					<div className="wc-block-components-checkbox">
						<label htmlFor="save_user_in_woopay">
							<input
								type="checkbox"
								checked={ isSavingUser }
								onChange={ ( event ) => {
									const checked = event.target.checked;
									const nextPhone = checked
										? phone || getWooPayInitialPhone()
										: '';
									if ( checked ) {
										setPhone( nextPhone );
									}
									updateSaveUser( checked, nextPhone );
								} }
								name="save_user_in_woopay"
								id="save_user_in_woopay"
								value="true"
								className="save-details-checkbox wc-block-components-checkbox__input"
							/>
							<svg
								className="wc-block-components-checkbox__mark"
								aria-hidden="true"
								xmlns="http://www.w3.org/2000/svg"
								viewBox="0 0 24 20"
							>
								<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z" />
							</svg>
							<span className="wc-block-components-checkbox__label">
								{ saveUserLabel }
							</span>
						</label>
					</div>
				</div>
				{ isSavingUser ? (
					<div className="save-details-form">
						<input
							type="hidden"
							name="woopay_source_url"
							value={ window.location.href }
							readOnly
						/>
						<input
							type="hidden"
							name="woopay_viewport"
							value={ getWooPayViewport() }
							readOnly
						/>
						<label htmlFor="woopay_user_phone_field_full">
							{ phoneLabel }
						</label>
						<input
							type="tel"
							id="woopay_user_phone_field_full"
							name="woopay_user_phone_field[full]"
							autoComplete="tel"
							value={ phone }
							onChange={ ( event ) =>
								setPhone( event.target.value )
							}
							onBlur={ () =>
								persistWooPaySaveUser( true, phone )
							}
						/>
					</div>
				) : null }
			</div>
		</div>
	);
};

const renderWooPaySaveUserSection = () => {
	if ( ! shouldRenderWooPaySaveUser() ) {
		return;
	}

	const paymentOptions = document.querySelector(
		'.wp-block-woocommerce-checkout-payment-block'
	);
	if ( ! paymentOptions ) {
		return;
	}

	let container = document.getElementById( 'remember-me' );
	if ( ! container ) {
		container = document.createElement( 'fieldset' );
		container.className =
			'wc-block-checkout__payment-method wp-block-woocommerce-checkout-remember-block wc-block-components-checkout-step';
		container.id = 'remember-me';
		paymentOptions.parentNode.insertBefore(
			container,
			paymentOptions.nextSibling
		);
	}

	if ( ! saveUserRoots.has( container ) ) {
		saveUserRoots.set( container, createRoot( container ) );
	}

	saveUserRoots.get( container ).render( <WooPaySaveUserSection /> );
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
	const accountStripe = useRef( null );
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

			if ( ! button || ! testNumber ) {
				return;
			}

			event.preventDefault();
			button.querySelector( 'i' )?.setAttribute( 'aria-hidden', 'true' );

			if (
				typeof window.navigator?.clipboard?.writeText === 'function'
			) {
				window.navigator.clipboard.writeText( testNumber );
			} else if ( typeof window.prompt === 'function' ) {
				// eslint-disable-next-line no-alert
				window.prompt(
					__( 'Copy test card number:', 'woocommerce' ),
					testNumber
				);
			}

			button.classList.add( 'state--success' );
			window.setTimeout( () => {
				button.classList.remove( 'state--success' );
			}, copyTestNumberSuccessDuration );
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
			getStripePaymentElementOptions( Boolean( shouldSavePayment ) )
		);
		paymentElement.current.mount( elementContainer.current );
	}, [ shouldSavePayment ] );

	useEffect( () => {
		renderWooPaySaveUserSection();
	}, [] );

	useEffect( () => {
		if ( ! onPaymentSetup ) {
			return undefined;
		}

		const unsubscribe = onPaymentSetup( async () => {
			recordWooPaymentsUserEvent(
				settings,
				'checkout_place_order_button_click'
			);

			const paymentMethodData = {
				'wcpay-payment-method': '',
				'wcpay-payment-method-error-code': '',
				'wcpay-payment-method-error-message': '',
				'wcpay-fingerprint': '',
				'wcpay-is-platform-payment-method':
					shouldUsePlatformStripeForCard() ? 'true' : 'false',
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

				if ( ! result.paymentMethod ) {
					return getErrorResponse(
						emitResponseRef.current,
						__(
							'There was a problem validating your payment details.',
							'woocommerce'
						)
					);
				}

				paymentMethodData[ 'wcpay-payment-method' ] =
					result.paymentMethod.id || '';
				paymentMethodData[ 'wcpay-fingerprint' ] =
					result.paymentMethod.card?.fingerprint || '';
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

			accountStripe.current =
				accountStripe.current || createStripe( true );

			if ( ! accountStripe.current ) {
				return getSuccessResponse( currentEmitResponse, {} );
			}

			let result;

			if ( confirmation.type === 'si' ) {
				result = confirmation.confirmationToken
					? await accountStripe.current.confirmSetup( {
							clientSecret: confirmation.clientSecret,
							confirmParams: {
								confirmation_token:
									confirmation.confirmationToken,
							},
							redirect: 'if_required',
					  } )
					: await accountStripe.current.handleNextAction( {
							clientSecret: confirmation.clientSecret,
					  } );
			} else {
				result = await accountStripe.current.handleNextAction( {
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
		showSavedCards: settings?.isSavedCardsEnabled ?? false,
		showSaveOption: cardConfig?.showSaveOption ?? false,
	},
} );

const registerWooPayments = () => {
	const paymentMethod = getWooPaymentsPaymentMethod();
	registerPaymentMethod( paymentMethod );

	return paymentMethod;
};

registerWooPayments();

export default registerWooPayments;
