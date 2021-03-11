/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from 'interpolate-components';
import {
	getAdminLink,
	WC_ASSET_URL as wcAssetUrl,
} from '@woocommerce/wc-admin-settings';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import Bacs from './bacs';
import BacsLogo from './images/bacs';
import CodLogo from './images/cod';
import WCPayLogo from './images/wcpay';
import RazorpayLogo from './images/razorpay';
import { MollieLogo } from './images/mollie';
import { PayUIndiaLogo } from './images/payu-india';
import Stripe from './stripe';
import Square from './square';
import {
	WCPay,
	WCPayUsageModal,
	installActivateAndConnectWcpay,
	isWCPaySupported,
} from './wcpay';
import PayPal, { PAYPAL_PLUGIN } from './paypal';
import Klarna from './klarna';
import PayFast from './payfast';
import EWay from './eway';
import Razorpay from './razorpay';
import { Mollie } from './mollie';
import { PayUIndia } from './payu-india';

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
			visible: ! hasCbdIndustry,
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
			container: <PayFast />,
			isConfigured:
				options.woocommerce_payfast_settings &&
				options.woocommerce_payfast_settings.merchant_id &&
				options.woocommerce_payfast_settings.merchant_key &&
				options.woocommerce_payfast_settings.pass_phrase,
			isEnabled:
				options.woocommerce_payfast_settings &&
				options.woocommerce_payfast_settings.enabled === 'yes',
			optionName: 'woocommerce_payfast_settings',
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
		},
	];

	if ( window.wcAdminFeatures.wcpay ) {
		const tosLink = (
			<Link
				href={ 'https://wordpress.com/tos/' }
				target="_blank"
				type="external"
			/>
		);

		const tosPrompt = interpolateComponents( {
			mixedString: __(
				'By clicking "Set up," you agree to the {{link}}Terms of Service{{/link}}',
				'woocommerce-admin'
			),
			components: {
				link: tosLink,
			},
		} );

		const wcPayDocLink = (
			<Link
				href={
					'https://docs.woocommerce.com/document/payments/testing/dev-mode/'
				}
				target="_blank"
				type="external"
			/>
		);

		const wcPayDocPrompt = interpolateComponents( {
			mixedString: __(
				'Setting up a store for a client? {{link}}Start here{{/link}}',
				'woocommerce-admin'
			),
			components: {
				link: wcPayDocLink,
			},
		} );

		const wcPaySettingsLink = (
			<Link
				href={ getAdminLink(
					'admin.php?page=wc-settings&tab=checkout&section=woocommerce_payments'
				) }
				type="wp-admin"
			>
				{ __( 'Settings', 'woocommerce-admin' ) }
			</Link>
		);
		const wcPayFeesLink = (
			<Link
				href={
					'https://docs.woocommerce.com/document/payments/faq/fees/'
				}
				target="_blank"
				type="external"
			/>
		);

		const wooPaymentsCopy = interpolateComponents( {
			mixedString: __(
				'Accept credit card payments the easy way! {{feesLink}}No setup fees. No monthly fees.{{/feesLink}}',
				'woocommerce-admin'
			),
			components: {
				feesLink: wcPayFeesLink,
			},
		} );

		methods.unshift( {
			key: 'wcpay',
			title: __( 'WooCommerce Payments', 'woocommerce-admin' ),
			content: (
				<>
					{ wooPaymentsCopy }
					{ wcPayIsConnected && wcPaySettingsLink }
					{ ! wcPayIsConnected && <p>{ tosPrompt }</p> }
					{ profileItems.setup_client && <p>{ wcPayDocPrompt }</p> }
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
		} );
	}

	return methods.filter( ( method ) => method.visible );
}
