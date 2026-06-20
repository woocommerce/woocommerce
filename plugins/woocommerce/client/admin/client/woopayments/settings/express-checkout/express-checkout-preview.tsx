/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	ExpressCheckoutInlineNotice,
	ExpressCheckoutPreviewFallback,
} from './components';
import { asSettingsRecord } from './settings-utils';
import {
	useGetSettings,
	usePaymentRequestButtonBorderRadius,
	usePaymentRequestButtonSize,
	usePaymentRequestButtonTheme,
	usePaymentRequestButtonType,
	usePaymentRequestEnabledSettings,
	useWooPayEnabledSettings,
} from '../data/hooks';

type StripePreviewConfig = {
	publishableKey?: string;
	accountId?: string;
	locale?: string;
};

type StripeExpressCheckoutElement = {
	mount: ( container: HTMLElement ) => void;
	unmount?: () => void;
	on: (
		eventName: 'ready' | 'loaderror',
		callback: ( event?: { availablePaymentMethods?: unknown } ) => void
	) => void;
};

type StripeElements = {
	create: (
		type: 'expressCheckout',
		options: Record< string, unknown >
	) => StripeExpressCheckoutElement;
};

type StripeInstance = {
	elements: ( options: Record< string, unknown > ) => StripeElements;
};

type StripeFactory = (
	publishableKey: string,
	options: {
		locale: string;
		stripeAccount?: string;
	}
) => StripeInstance;

const BUTTON_HEIGHT_BY_SIZE: Record< string, number > = {
	small: 40,
	medium: 48,
	large: 55,
};
const STRIPE_SCRIPT_ID = 'woopayments-settings-stripe-js';
const STRIPE_SCRIPT_URL = 'https://js.stripe.com/v3/';
let stripeScriptPromise: Promise< StripeFactory | undefined > | null = null;

const isRecord = ( value: unknown ): value is Record< string, unknown > =>
	!! value && typeof value === 'object' && ! Array.isArray( value );

const getStripeFactory = (): StripeFactory | undefined =>
	( window as typeof window & { Stripe?: StripeFactory } ).Stripe;

const loadStripeFactory = (): Promise< StripeFactory | undefined > => {
	const existingFactory = getStripeFactory();

	if ( existingFactory ) {
		return Promise.resolve( existingFactory );
	}

	if ( stripeScriptPromise ) {
		return stripeScriptPromise;
	}

	stripeScriptPromise = new Promise( ( resolve ) => {
		let script = document.getElementById(
			STRIPE_SCRIPT_ID
		) as HTMLScriptElement | null;
		const resolveFactory = () => {
			const factory = getStripeFactory();
			if ( script ) {
				script.dataset.woopaymentsLoadState = factory
					? 'loaded'
					: 'failed';
				if ( ! factory ) {
					script.remove();
				}
			}
			stripeScriptPromise = null;
			resolve( factory );
		};
		const resolveUnavailable = () => {
			if ( script ) {
				script.dataset.woopaymentsLoadState = 'failed';
				script.remove();
			}
			stripeScriptPromise = null;
			resolve( undefined );
		};

		if ( script && script.dataset.woopaymentsLoadState !== 'loading' ) {
			script.remove();
			script = null;
		}

		if ( ! script ) {
			script = document.createElement( 'script' );
			script.id = STRIPE_SCRIPT_ID;
			script.src = STRIPE_SCRIPT_URL;
			script.async = true;
			script.dataset.woopaymentsLoadState = 'loading';
			document.head.appendChild( script );
		}

		script.addEventListener( 'load', resolveFactory, { once: true } );
		script.addEventListener( 'error', resolveUnavailable, { once: true } );
	} );

	return stripeScriptPromise;
};

const getButtonHeight = ( size: string ): number =>
	BUTTON_HEIGHT_BY_SIZE[ size ] || BUTTON_HEIGHT_BY_SIZE.medium;

const getStripeButtonTheme = (
	paymentMethod: 'applePay' | 'googlePay',
	theme: string
): string => {
	if ( theme === 'light' ) {
		return 'white';
	}

	if ( theme === 'light-outline' ) {
		return paymentMethod === 'googlePay' ? 'white' : 'white-outline';
	}

	return 'black';
};

const getStripeButtonType = ( type: string ): string =>
	type === 'default' ? 'plain' : type;

const getStripePreviewConfig = (
	settings: Record< string, unknown >
): StripePreviewConfig => {
	const preview = settings.express_checkout_preview;
	if ( ! isRecord( preview ) || ! isRecord( preview.stripe ) ) {
		return {};
	}

	return {
		publishableKey:
			typeof preview.stripe.publishableKey === 'string'
				? preview.stripe.publishableKey
				: '',
		accountId:
			typeof preview.stripe.accountId === 'string'
				? preview.stripe.accountId
				: '',
		locale:
			typeof preview.stripe.locale === 'string'
				? preview.stripe.locale
				: 'auto',
	};
};

const ActivateExpressCheckoutNotice = () => (
	<ExpressCheckoutInlineNotice status="info">
		{ __(
			'To preview the express checkout buttons, activate at least one express checkout.',
			'woocommerce'
		) }
	</ExpressCheckoutInlineNotice>
);

const FailedAppleGooglePayPreviewNotice = () => (
	<ExpressCheckoutInlineNotice status="error">
		{ __(
			"Failed to preview the Apple Pay or Google Pay button. Ensure your store uses HTTPS on a publicly available domain and you're viewing this page in a Safari or Chrome browser. Your device must be configured to use Apple Pay or Google Pay.",
			'woocommerce'
		) }
	</ExpressCheckoutInlineNotice>
);

const WooPayButtonPreview = ( {
	buttonType,
	height,
	radius,
	theme,
}: {
	buttonType: string;
	height: number;
	radius: number;
	theme: string;
} ) => {
	const actionLabelByType: Record< string, string > = {
		buy: __( 'Buy with WooPay', 'woocommerce' ),
		donate: __( 'Donate with WooPay', 'woocommerce' ),
		book: __( 'Book with WooPay', 'woocommerce' ),
	};
	const label =
		actionLabelByType[ buttonType ] || __( 'WooPay', 'woocommerce' );
	const descriptionId =
		'woopayments-express-checkout-settings__woopay-button-preview-description';

	return (
		<>
			<button
				type="button"
				className="woopayments-express-checkout-settings__woopay-button-preview"
				data-theme={ theme }
				aria-describedby={ descriptionId }
				aria-disabled="true"
				tabIndex={ -1 }
				style={ {
					minHeight: `${ height }px`,
					borderRadius: `${ radius }px`,
				} }
				onClick={ ( event ) => event.preventDefault() }
			>
				{ label }
			</button>
			<span id={ descriptionId } className="screen-reader-text">
				{ __( 'Express checkout preview', 'woocommerce' ) }
			</span>
		</>
	);
};

const AppleGooglePayPreview = ( {
	buttonType,
	height,
	radius,
	stripeConfig,
	theme,
}: {
	buttonType: string;
	height: number;
	radius: number;
	stripeConfig: StripePreviewConfig;
	theme: string;
} ) => {
	const containerRef = useRef< HTMLDivElement | null >( null );
	const [ hasPreviewError, setHasPreviewError ] = useState( false );

	useEffect( () => {
		const publishableKey = stripeConfig.publishableKey;

		if ( ! containerRef.current || ! publishableKey ) {
			return;
		}

		let expressElement: StripeExpressCheckoutElement | null = null;
		let isCurrent = true;
		setHasPreviewError( false );

		const mountPreview = async () => {
			const stripeFactory = await loadStripeFactory();
			if ( ! isCurrent ) {
				return;
			}

			if ( ! stripeFactory || ! containerRef.current ) {
				setHasPreviewError( true );
				return;
			}

			try {
				const stripe = stripeFactory( publishableKey, {
					locale: stripeConfig.locale || 'auto',
					...( stripeConfig.accountId
						? { stripeAccount: stripeConfig.accountId }
						: {} ),
				} );
				const elements = stripe.elements( {
					mode: 'payment',
					amount: 1000,
					currency: 'usd',
					loader: 'never',
					appearance: {
						variables: {
							borderRadius: `${ radius }px`,
							spacingUnit: '6px',
						},
					},
				} );

				expressElement = elements.create( 'expressCheckout', {
					buttonHeight: Math.min( Math.max( height, 40 ), 55 ),
					buttonTheme: {
						applePay: getStripeButtonTheme( 'applePay', theme ),
						googlePay: getStripeButtonTheme( 'googlePay', theme ),
					},
					buttonType: {
						applePay: getStripeButtonType( buttonType ),
						googlePay: getStripeButtonType( buttonType ),
					},
					paymentMethods: {
						amazonPay: 'never',
						link: 'never',
						paypal: 'never',
						klarna: 'never',
						googlePay: 'always',
						applePay: 'always',
					},
					layout: { overflow: 'never' },
				} );
				expressElement.on( 'ready', ( event ) => {
					if ( isCurrent ) {
						setHasPreviewError( ! event?.availablePaymentMethods );
					}
				} );
				expressElement.on( 'loaderror', () => {
					if ( isCurrent ) {
						setHasPreviewError( true );
					}
				} );
				expressElement.mount( containerRef.current );
			} catch {
				setHasPreviewError( true );
			}
		};

		mountPreview();

		return () => {
			isCurrent = false;
			expressElement?.unmount?.();
		};
	}, [
		buttonType,
		height,
		radius,
		stripeConfig.accountId,
		stripeConfig.locale,
		stripeConfig.publishableKey,
		theme,
	] );

	if ( hasPreviewError ) {
		return <FailedAppleGooglePayPreviewNotice />;
	}

	return (
		<div
			ref={ containerRef }
			className="woopayments-express-checkout-settings__stripe-preview"
			style={ { minHeight: `${ height }px` } }
		/>
	);
};

export const ExpressCheckoutPreview = () => {
	const settings = asSettingsRecord( useGetSettings() );
	const [ buttonType ] = usePaymentRequestButtonType() as [ string ];
	const [ size ] = usePaymentRequestButtonSize() as [ string ];
	const [ theme ] = usePaymentRequestButtonTheme() as [ string ];
	const [ radius ] = usePaymentRequestButtonBorderRadius() as [ number ];
	const [ isWooPayEnabled ] = useWooPayEnabledSettings() as [ boolean ];
	const [ isPaymentRequestEnabled ] = usePaymentRequestEnabledSettings() as [
		boolean
	];
	const height = getButtonHeight( size );
	const stripeConfig = getStripePreviewConfig( settings );
	const canAttemptAppleGooglePayPreview =
		window.location.protocol === 'https:' && !! stripeConfig.publishableKey;

	if ( ! isWooPayEnabled && ! isPaymentRequestEnabled ) {
		return (
			<div className="woopayments-express-checkout-settings__preview">
				<ActivateExpressCheckoutNotice />
			</div>
		);
	}

	return (
		<div className="woopayments-express-checkout-settings__preview woopayments-express-checkout-settings__preview-stack">
			{ isWooPayEnabled && (
				<WooPayButtonPreview
					buttonType={ buttonType }
					height={ height }
					radius={ Number( radius || 0 ) }
					theme={ theme }
				/>
			) }
			{ isPaymentRequestEnabled &&
				( canAttemptAppleGooglePayPreview ? (
					<AppleGooglePayPreview
						buttonType={ buttonType }
						height={ height }
						radius={ Number( radius || 0 ) }
						stripeConfig={ stripeConfig }
						theme={ theme }
					/>
				) : (
					<ExpressCheckoutPreviewFallback />
				) ) }
		</div>
	);
};
