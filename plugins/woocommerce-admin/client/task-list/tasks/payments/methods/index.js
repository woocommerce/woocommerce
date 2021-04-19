/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	getAdminLink,
	getSetting,
	WC_ASSET_URL as wcAssetUrl,
} from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import Bacs from './bacs';
import BacsLogo from '../images/bacs';
import CodLogo from '../images/cod';
import WCPayLogo from '../images/wcpay';
import RazorpayLogo from '../images/razorpay';
import { MollieLogo } from '../images/mollie';
import { PayUIndiaLogo } from '../images/payu-india';
import Stripe from './stripe';
import Square from './square';
import {
	WCPay,
	WCPayUsageModal,
	installActivateAndConnectWcpay,
	isWCPaySupported,
} from './wcpay';
import PayPal, { PAYPAL_PLUGIN } from './paypal';
import { MercadoPago, MERCADOPAGO_PLUGIN } from './mercadopago';
import Klarna from './klarna';
import EWay from './eway';
import Razorpay from './razorpay';
import { Mollie } from './mollie';
import { PayUIndia } from './payu-india';
import { GenericPaymentStep } from '../generic-payment-step';

const wcAdminAssetUrl = getSetting( 'wcAdminAssetUrl', '' );

const getPaymentsSettingsUrl = ( methodKey ) => {
	return getAdminLink(
		'admin.php?page=wc-settings&tab=checkout&section=' + methodKey
	);
};

const methodDefaults = { isConfigured: true };

export function getPaymentMethods( {
	activePlugins,
	countryCode,
	createNotice,
	installAndActivatePlugins,
	onboardingStatus,
	options,
	profileItems,
	paypalOnboardingStatus,
	loadingPaypalStatus,
} ) {
	const {
		stripeSupportedCountries = [],
		wcPayIsConnected = false,
		enabledPaymentGateways = [],
	} = onboardingStatus;

	const hasCbdIndustry = ( profileItems.industry || [] ).some(
		( { slug } ) => {
			return slug === 'cbd-other-hemp-derived-products';
		}
	);

	// Whether publishable and secret keys are filled for given mode.
	const isStripeConfigured =
		options.woocommerce_stripe_settings &&
		( options.woocommerce_stripe_settings.testmode === 'no'
			? options.woocommerce_stripe_settings.publishable_key &&
			  options.woocommerce_stripe_settings.secret_key
			: options.woocommerce_stripe_settings.test_publishable_key &&
			  options.woocommerce_stripe_settings.test_secret_key );

	const methods = [
		{
			key: 'stripe',
			title: __(
				'Credit cards - powered by Stripe',
				'woocommerce-admin'
			),
			content: (
				<>
					{ __(
						'Accept debit and credit cards in 135+ currencies, methods such as Alipay, ' +
							'and one-touch checkout with Apple Pay.',
						'woocommerce-admin'
					) }
				</>
			),
			before: <img src={ wcAssetUrl + 'images/stripe.png' } alt="" />,
			visible:
				stripeSupportedCountries.includes( countryCode ) &&
				! hasCbdIndustry,
			plugins: [ 'woocommerce-gateway-stripe' ],
			container: <Stripe />,
			isConfigured: isStripeConfigured,
			isEnabled:
				options.woocommerce_stripe_settings &&
				options.woocommerce_stripe_settings.enabled === 'yes',
			optionName: 'woocommerce_stripe_settings',
			manageUrl: getPaymentsSettingsUrl( 'stripe' ),
		},
		{
			key: 'paystack',
			title: __( 'Paystack', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						'Paystack helps African merchants accept one-time and recurring payments online with a modern, safe, and secure payment gateway.',
						'woocommerce-admin'
					) }
				</>
			),
			before: (
				<img
					src={ wcAdminAssetUrl + 'onboarding/paystack.png' }
					alt="Paystack logo"
				/>
			),
			visible:
				[ 'ZA', 'GH', 'NG' ].includes( countryCode ) &&
				! hasCbdIndustry,
			plugins: [ 'woo-paystack' ],
			container: <GenericPaymentStep />,
			isConfigured:
				options.woocommerce_paystack_settings &&
				options.woocommerce_paystack_settings.live_public_key &&
				options.woocommerce_paystack_settings.live_secret_key,
			isEnabled: enabledPaymentGateways.includes( 'paystack' ),
			optionName: 'woocommerce_paystack_settings',
			apiDetailsLink:
				'https://dashboard.paystack.com/#/settings/developer',
			fields: [
				{
					name: 'live_public_key',
					title: __( 'Live Public Key', 'woocommerce-admin' ),
				},
				{
					name: 'live_secret_key',
					title: __( 'Live Secret Key', 'woocommerce-admin' ),
				},
			],
			getOptions: ( values ) => {
				// Paystack only supports NGN (₦), GHS (₵), USD ($) or ZAR (R)
				return {
					woocommerce_currency: 'ZAR',
					woocommerce_paystack_settings: {
						...values,
						testmode: 'no',
					},
				};
			},
			manageUrl: getPaymentsSettingsUrl( 'paystack' ),
		},
		{
			key: 'payfast',
			title: __( 'PayFast', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						'The PayFast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs.',
						'woocommerce-admin'
					) }
					<p>
						{ __(
							'Selecting this extension will configure your store to use South African rands as the selected currency.',
							'woocommerce-admin'
						) }
					</p>
				</>
			),
			before: (
				<img
					src={ wcAssetUrl + 'images/payfast.png' }
					alt="PayFast logo"
				/>
			),
			visible: [ 'ZA' ].includes( countryCode ) && ! hasCbdIndustry,
			plugins: [ 'woocommerce-payfast-gateway' ],
			container: <GenericPaymentStep />,
			isConfigured:
				options.woocommerce_payfast_settings &&
				options.woocommerce_payfast_settings.merchant_id &&
				options.woocommerce_payfast_settings.merchant_key &&
				options.woocommerce_payfast_settings.pass_phrase,
			isEnabled:
				options.woocommerce_payfast_settings &&
				options.woocommerce_payfast_settings.enabled === 'yes',
			optionName: 'woocommerce_payfast_settings',
			apiDetailsLink: 'https://www.payfast.co.za/',
			fields: [
				{
					name: 'merchant_id',
					title: __( 'Merchant ID', 'woocommerce-admin' ),
				},
				{
					name: 'merchant_key',
					title: __( 'Merchant Key', 'woocommerce-admin' ),
				},
				{
					name: 'pass_phrase',
					title: __( 'Passphrase', 'woocommerce-admin' ),
				},
			],
			getOptions: ( values ) => {
				return {
					woocommerce_currency: 'ZAR',
					woocommerce_payfast_settings: {
						...values,
						testmode: 'no',
					},
				};
			},
			manageUrl: getPaymentsSettingsUrl( 'stripe' ),
		},
		{
			key: 'mercadopago',
			title: __(
				'Mercado Pago Checkout Pro & Custom',
				'woocommerce-admin'
			),
			content: (
				<>
					{ __(
						'Accept credit and debit cards, offline (cash or bank transfer) and logged-in payments with money in Mercado Pago. Safe and secure payments with the leading payment processor in LATAM.',
						'woocommerce-admin'
					) }
				</>
			),
			before: (
				<img
					src={ wcAdminAssetUrl + 'onboarding/mercadopago.png' }
					alt=""
				/>
			),
			visible: [ 'AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY' ].includes(
				countryCode
			),
			plugins: [ MERCADOPAGO_PLUGIN ],
			container: <MercadoPago />,
			isConfigured: activePlugins.includes( MERCADOPAGO_PLUGIN ),
			isEnabled:
				options[ 'woocommerce_woo-mercado-pago-basic_settings' ] &&
				options[ 'woocommerce_woo-mercado-pago-basic_settings' ]
					.enabled === 'yes',
			optionName: 'woocommerce_woo-mercado-pago-basic_settings',
			manageUrl: getPaymentsSettingsUrl( 'woo-mercado-pago-basic' ),
		},
		{
			key: 'paypal',
			title: __( 'PayPal Payments', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						"Safe and secure payments using credit cards or your customer's PayPal account.",
						'woocommerce-admin'
					) }
				</>
			),
			before: <img src={ wcAssetUrl + 'images/paypal.png' } alt="" />,
			visible: countryCode !== 'IN' && ! hasCbdIndustry,
			plugins: [ PAYPAL_PLUGIN ],
			container: <PayPal />,
			isConfigured:
				paypalOnboardingStatus &&
				paypalOnboardingStatus.production &&
				paypalOnboardingStatus.production.onboarded,
			isEnabled: enabledPaymentGateways.includes( 'ppcp-gateway' ),
			optionName: 'woocommerce_ppcp-gateway_settings',
			loading: activePlugins.includes( PAYPAL_PLUGIN )
				? loadingPaypalStatus
				: false,
			manageUrl: getPaymentsSettingsUrl( 'ppcp-gateway' ),
		},
		{
			key: 'klarna_checkout',
			title: __( 'Klarna Checkout', 'woocommerce-admin' ),
			content: __(
				'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.',
				'woocommerce-admin'
			),
			before: (
				<img src={ wcAssetUrl + 'images/klarna-black.png' } alt="" />
			),
			visible:
				[ 'SE', 'FI', 'NO' ].includes( countryCode ) &&
				! hasCbdIndustry,
			plugins: [ 'klarna-checkout-for-woocommerce' ],
			container: <Klarna plugin={ 'checkout' } />,
			// @todo This should check actual Klarna connection information.
			isConfigured: activePlugins.includes(
				'klarna-checkout-for-woocommerce'
			),
			isEnabled:
				options.woocommerce_kco_settings &&
				options.woocommerce_kco_settings.enabled === 'yes',
			optionName: 'woocommerce_kco_settings',
			manageUrl: getPaymentsSettingsUrl( 'kco' ),
		},
		{
			key: 'klarna_payments',
			title: __( 'Klarna Payments', 'woocommerce-admin' ),
			content: __(
				'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.',
				'woocommerce-admin'
			),
			before: (
				<img src={ wcAssetUrl + 'images/klarna-black.png' } alt="" />
			),
			visible:
				[
					'DK',
					'DE',
					'AT',
					'NL',
					'CH',
					'BE',
					'SP',
					'PL',
					'FR',
					'IT',
					'GB',
				].includes( countryCode ) && ! hasCbdIndustry,
			plugins: [ 'klarna-payments-for-woocommerce' ],
			container: <Klarna plugin={ 'payments' } />,
			// @todo This should check actual Klarna connection information.
			isConfigured: activePlugins.includes(
				'klarna-payments-for-woocommerce'
			),
			isEnabled:
				options.woocommerce_klarna_payments_settings &&
				options.woocommerce_klarna_payments_settings.enabled === 'yes',
			optionName: 'woocommerce_klarna_payments_settings',
			manageUrl: getPaymentsSettingsUrl( 'klarna_payments' ),
		},
		{
			key: 'mollie',
			title: __( 'Mollie Payments for WooCommerce', 'woocommerce-admin' ),
			before: <MollieLogo />,
			plugins: [ 'mollie-payments-for-woocommerce' ],
			isConfigured: activePlugins.includes(
				'mollie-payments-for-woocommerce'
			),
			content: (
				<>
					{ __(
						'Effortless payments by Mollie: Offer global and local payment methods, get onboarded in minutes, and supported in your language.',
						'woocommerce-admin'
					) }
				</>
			),
			visible: [
				'FR',
				'DE',
				'GB',
				'AT',
				'CH',
				'ES',
				'IT',
				'PL',
				'FI',
				'NL',
				'BE',
			].includes( countryCode ),
			container: <Mollie />,
			isEnabled:
				options.woocommerce_mollie_payments_settings &&
				options.woocommerce_mollie_payments_settings.enabled === 'yes',
			optionName: 'woocommerce_mollie_payments_settings',
			manageUrl: getPaymentsSettingsUrl( 'mollie_wc_gateway_creditcard' ),
		},
		{
			key: 'square',
			title: __( 'Square', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). ' +
							'Sell online and in store and track sales and inventory in one place.',
						'woocommerce-admin'
					) }
					{ hasCbdIndustry && (
						<span className="text-style-strong">
							{ __(
								' Selling CBD products is only supported by Square.',
								'woocommerce-admin'
							) }
						</span>
					) }
				</>
			),
			before: (
				<img src={ `${ wcAssetUrl }images/square-black.png` } alt="" />
			),
			visible:
				( hasCbdIndustry && [ 'US' ].includes( countryCode ) ) ||
				( [ 'brick-mortar', 'brick-mortar-other' ].includes(
					profileItems.selling_venues
				) &&
					[ 'US', 'CA', 'JP', 'GB', 'AU', 'IE' ].includes(
						countryCode
					) ),
			plugins: [ 'woocommerce-square' ],
			container: <Square />,
			isConfigured:
				options.wc_square_refresh_tokens &&
				options.wc_square_refresh_tokens.length,
			isEnabled:
				options.woocommerce_square_credit_card_settings &&
				options.woocommerce_square_credit_card_settings.enabled ===
					'yes',
			optionName: 'woocommerce_square_credit_card_settings',
			hasCbdIndustry,
			manageUrl: getPaymentsSettingsUrl( 'square_credit_card' ),
		},
		{
			key: 'eway',
			title: __( 'eWAY', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						'The eWAY extension for WooCommerce allows you to take credit card payments directly on your store without redirecting your customers to a third party site to make payment.',
						'woocommerce-admin'
					) }
				</>
			),
			before: (
				<img
					src={ wcAssetUrl + 'images/eway-logo.jpg' }
					alt="eWAY logo"
				/>
			),
			visible: [ 'AU', 'NZ' ].includes( countryCode ) && ! hasCbdIndustry,
			plugins: [ 'woocommerce-gateway-eway' ],
			container: <EWay />,
			isConfigured:
				options.woocommerce_eway_settings &&
				options.woocommerce_eway_settings.customer_api &&
				options.woocommerce_eway_settings.customer_password,
			isEnabled:
				options.woocommerce_eway_settings &&
				options.woocommerce_eway_settings.enabled === 'yes',
			optionName: 'woocommerce_eway_settings',
			manageUrl: getPaymentsSettingsUrl( 'eway' ),
		},
		{
			key: 'razorpay',
			title: __( 'Razorpay', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						'The official Razorpay extension for WooCommerce allows you to accept credit cards, debit cards, netbanking, wallet, and UPI payments.',
						'woocommerce-admin'
					) }
				</>
			),
			before: <RazorpayLogo />,
			visible: countryCode === 'IN' && ! hasCbdIndustry,
			plugins: [ 'woo-razorpay' ],
			container: <Razorpay />,
			isConfigured:
				options.woocommerce_razorpay_settings &&
				options.woocommerce_razorpay_settings.key_id &&
				options.woocommerce_razorpay_settings.key_secret,
			isEnabled:
				options.woocommerce_razorpay_settings &&
				options.woocommerce_razorpay_settings.enabled === 'yes',
			optionName: 'woocommerce_razorpay_settings',
			manageUrl: getPaymentsSettingsUrl( 'razorpay' ),
		},
		{
			key: 'payubiz',
			title: __( 'PayU for WooCommerce', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						'Enable PayU’s exclusive plugin for WooCommerce to start accepting payments in 100+ payment methods available in India including credit cards, debit cards, UPI, & more!',
						'woocommerce-admin'
					) }
				</>
			),
			before: <PayUIndiaLogo />,
			visible: countryCode === 'IN' && ! hasCbdIndustry,
			plugins: [ 'payu-india' ],
			container: <PayUIndia />,
			isConfigured: activePlugins.includes( 'payu-india' ),
			isEnabled: enabledPaymentGateways.includes( 'payubiz' ),
			optionName: 'woocommerce_payubiz_settings',
			manageUrl: getPaymentsSettingsUrl( 'payubiz' ),
		},
		{
			key: 'cod',
			title: __( 'Cash on delivery', 'woocommerce-admin' ),
			content: __(
				'Take payments in cash upon delivery.',
				'woocommerce-admin'
			),
			before: <CodLogo />,
			visible: ! hasCbdIndustry,
			isEnabled:
				options.woocommerce_cod_settings &&
				options.woocommerce_cod_settings.enabled === 'yes',
			optionName: 'woocommerce_cod_settings',
			manageUrl: getPaymentsSettingsUrl( 'cod' ),
		},
		{
			key: 'bacs',
			title: __( 'Direct bank transfer', 'woocommerce-admin' ),
			content: __(
				'Take payments via bank transfer.',
				'woocommerce-admin'
			),
			before: <BacsLogo />,
			visible: ! hasCbdIndustry,
			container: <Bacs />,
			isConfigured:
				options.woocommerce_bacs_accounts &&
				options.woocommerce_bacs_accounts.length,
			isEnabled:
				options.woocommerce_bacs_settings &&
				options.woocommerce_bacs_settings.enabled === 'yes',
			optionName: 'woocommerce_bacs_settings',
			manageUrl: getPaymentsSettingsUrl( 'bacs' ),
		},
	];

	if ( window.wcAdminFeatures.wcpay ) {
		methods.unshift( {
			key: 'wcpay',
			title: __( 'WooCommerce Payments', 'woocommerce-admin' ),
			content: (
				<>
					{ __(
						'Manage transactions without leaving your WordPress Dashboard. Only with WooCommerce Payments.',
						'woocommerce-admin'
					) }
					<WCPayUsageModal />
				</>
			),
			before: <WCPayLogo />,
			onClick: ( resolve, reject ) => {
				return installActivateAndConnectWcpay(
					reject,
					createNotice,
					installAndActivatePlugins
				);
			},
			visible: isWCPaySupported( countryCode ) && ! hasCbdIndustry,
			plugins: [ 'woocommerce-payments' ],
			container: <WCPay />,
			isConfigured: wcPayIsConnected,
			isEnabled:
				options.woocommerce_woocommerce_payments_settings &&
				options.woocommerce_woocommerce_payments_settings.enabled ===
					'yes',
			optionName: 'woocommerce_woocommerce_payments_settings',
			manageUrl: getPaymentsSettingsUrl( 'woocommerce_payments' ),
		} );
	}

	return methods
		.filter( ( method ) => method.visible )
		.map( ( method ) => ( { ...methodDefaults, ...method } ) );
}
