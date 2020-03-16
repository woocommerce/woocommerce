/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { filter } from 'lodash';

/**
 * WooCommerce dependencies
 */
import {
	getSetting,
	WC_ASSET_URL as wcAssetUrl,
} from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import Stripe from './stripe';
import Square from './square';
import PayPal from './paypal';
import Klarna from './klarna';
import PayFast from './payfast';

export function getPaymentMethods( {
	activePlugins,
	countryCode,
	options,
	profileItems,
} ) {
	const stripeCountries = getSetting( 'onboarding', {
		stripeSupportedCountries: [],
	} ).stripeSupportedCountries;

	const methods = [
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
			visible: stripeCountries.includes( countryCode ),
			plugins: [ 'woocommerce-gateway-stripe' ],
			container: <Stripe />,
			isConfigured:
				options.woocommerce_stripe_settings &&
				options.woocommerce_stripe_settings.publishable_key &&
				options.woocommerce_stripe_settings.secret_key,
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
			visible: true,
			plugins: [ 'woocommerce-gateway-paypal-express-checkout' ],
			container: <PayPal />,
			isConfigured:
				options.woocommerce_ppec_paypal_settings &&
				options.woocommerce_ppec_paypal_settings.api_username &&
				options.woocommerce_ppec_paypal_settings.api_password,
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
			visible: [ 'SE', 'FI', 'NO', 'NL' ].includes( countryCode ),
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
			visible: [ 'DK', 'DE', 'AT' ].includes( countryCode ),
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
			content: __(
				'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). ' +
					'Sell online and in store and track sales and inventory in one place.',
				'woocommerce-admin'
			),
			before: (
				<img src={ wcAssetUrl + 'images/square-black.png' } alt="" />
			),
			visible:
				[ 'brick-mortar', 'brick-mortar-other' ].includes(
					profileItems.selling_venues
				) && [ 'US', 'CA', 'JP', 'GB', 'AU' ].includes( countryCode ),
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
			visible: [ 'ZA' ].includes( countryCode ),
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
	];

	return filter( methods, ( method ) => method.visible );
}
