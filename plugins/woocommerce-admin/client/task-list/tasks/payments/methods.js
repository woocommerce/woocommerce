/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Fragment } from '@wordpress/element';
import { filter, some } from 'lodash';
import interpolateComponents from 'interpolate-components';
import {
	getAdminLink,
	WC_ASSET_URL as wcAssetUrl,
} from '@woocommerce/wc-admin-settings';
import { Link } from '@woocommerce/components';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Bacs from './bacs';
import BacsIcon from './images/bacs';
import CodIcon from './images/cod';
import { createNoticesFromResponse } from '../../../lib/notices';
import Stripe from './stripe';
import Square from './square';
import WCPay from './wcpay';
import WCPayIcon from './images/wcpay';
import PayPal from './paypal';
import Klarna from './klarna';
import PayFast from './payfast';
import EWay from './eway';

export function installActivateAndConnectWcpay(
	resolve,
	reject,
	createNotice,
	installAndActivatePlugins
) {
	const errorMessage = __(
		'There was an error connecting to WooCommerce Payments. Please try again or connect later in store settings.',
		'woocommerce-admin'
	);

	const connect = () => {
		apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/plugins/connect-wcpay',
			method: 'POST',
		} )
			.then( ( response ) => {
				window.location = response.connectUrl;
			} )
			.catch( () => {
				createNotice( 'error', errorMessage );
				reject();
			} );
	};

	installAndActivatePlugins( [ 'woocommerce-payments' ] )
		.then( () => connect() )
		.catch( ( error ) => {
			createNoticesFromResponse( error );
			reject();
		} );
}

export function getPaymentMethods( {
	activePlugins,
	countryCode,
	createNotice,
	installAndActivatePlugins,
	onboardingStatus,
	options,
	profileItems,
} ) {
	const {
		stripeSupportedCountries = [],
		wcPayIsConnected = false,
	} = onboardingStatus;

	const hasCbdIndustry =
		some( profileItems.industry, {
			slug: 'cbd-other-hemp-derived-products',
		} ) || false;

	const methods = [];

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

		methods.push( {
			key: 'wcpay',
			title: __( 'WooCommerce Payments', 'woocommerce-admin' ),
			content: (
				<Fragment>
					{ __(
						'Accept credit card payments the easy way! No setup fees. No ' +
							'monthly fees. Just 2.9% + $0.30 per transaction ' +
							'on U.S. issued cards. ',
						'woocommerce-admin'
					) }
					{ wcPayIsConnected && wcPaySettingsLink }
					{ ! wcPayIsConnected && <p>{ tosPrompt }</p> }
					{ profileItems.setup_client && <p>{ wcPayDocPrompt }</p> }
				</Fragment>
			),
			before: <WCPayIcon />,
			onClick: ( resolve, reject ) => {
				return installActivateAndConnectWcpay(
					resolve,
					reject,
					createNotice,
					installAndActivatePlugins
				);
			},
			visible: [ 'US', 'PR' ].includes( countryCode ) && ! hasCbdIndustry,
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

	// Whether publishable and secret keys are filled for given mode.
	const isStripeConfigured =
		options.woocommerce_stripe_settings &&
		( options.woocommerce_stripe_settings.testmode === 'no'
			? options.woocommerce_stripe_settings.publishable_key &&
			  options.woocommerce_stripe_settings.secret_key
			: options.woocommerce_stripe_settings.test_publishable_key &&
			  options.woocommerce_stripe_settings.test_secret_key );

	methods.push(
		{
			key: 'stripe',
			title: __(
				'Credit cards - powered by Stripe',
				'woocommerce-admin'
			),
			content: (
				<Fragment>
					{ __(
						'Accept debit and credit cards in 135+ currencies, methods such as Alipay, ' +
							'and one-touch checkout with Apple Pay.',
						'woocommerce-admin'
					) }
				</Fragment>
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
			title: __( 'PayPal Checkout', 'woocommerce-admin' ),
			content: (
				<Fragment>
					{ __(
						"Safe and secure payments using credit cards or your customer's PayPal account.",
						'woocommerce-admin'
					) }
				</Fragment>
			),
			before: <img src={ wcAssetUrl + 'images/paypal.png' } alt="" />,
			visible: ! hasCbdIndustry,
			plugins: [ 'woocommerce-gateway-paypal-express-checkout' ],
			container: <PayPal />,
			isConfigured:
				options.woocommerce_ppec_paypal_settings &&
				( ( options.woocommerce_ppec_paypal_settings.reroute_requests &&
					options.woocommerce_ppec_paypal_settings.email ) ||
					( options.woocommerce_ppec_paypal_settings.api_username &&
						options.woocommerce_ppec_paypal_settings
							.api_password ) ),
			isEnabled:
				options.woocommerce_ppec_paypal_settings &&
				options.woocommerce_ppec_paypal_settings.enabled === 'yes',
			optionName: 'woocommerce_ppec_paypal_settings',
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
				[ 'SE', 'FI', 'NO', 'NL' ].includes( countryCode ) &&
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
				[ 'DK', 'DE', 'AT' ].includes( countryCode ) &&
				! hasCbdIndustry,
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
			key: 'square',
			title: __( 'Square', 'woocommerce-admin' ),
			content: (
				<Fragment>
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
				</Fragment>
			),
			before: (
				<img src={ wcAssetUrl + 'images/square-black.png' } alt="" />
			),
			visible:
				( hasCbdIndustry && [ 'US' ].includes( countryCode ) ) ||
				( [ 'brick-mortar', 'brick-mortar-other' ].includes(
					profileItems.selling_venues
				) &&
					[ 'US', 'CA', 'JP', 'GB', 'AU' ].includes( countryCode ) ),
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
				<Fragment>
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
				</Fragment>
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
				<Fragment>
					{ __(
						'The eWAY extension for WooCommerce allows you to take credit card payments directly on your store without redirecting your customers to a third party site to make payment.',
						'woocommerce-admin'
					) }
				</Fragment>
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
			key: 'cod',
			title: __( 'Cash on delivery', 'woocommerce-admin' ),
			content: __(
				'Take payments in cash upon delivery.',
				'woocommerce-admin'
			),
			before: <CodIcon />,
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
			before: <BacsIcon />,
			visible: ! hasCbdIndustry,
			container: <Bacs />,
			isConfigured:
				options.woocommerce_bacs_accounts &&
				options.woocommerce_bacs_accounts.length,
			isEnabled:
				options.woocommerce_bacs_settings &&
				options.woocommerce_bacs_settings.enabled === 'yes',
			optionName: 'woocommerce_bacs_settings',
		}
	);

	return filter( methods, ( method ) => method.visible );
}
