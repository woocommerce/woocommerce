/* eslint-disable max-len */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import React from 'react';

const documentationUrls = {
	tos: 'https://wordpress.com/tos/',
	merchantTerms: 'https://wordpress.com/tos/#more-woopay-specifically',
	privacyPolicy: 'https://automattic.com/privacy/',
};

export default {
	steps: {
		business: {
			heading: __(
				'Let’s get your store ready to accept payments',
				'woocommerce-payments'
			),
			subheading: __(
				'We’ll use these details to enable payments for your store. This information can’t be changed after your account is created.',
				'woocommerce-payments'
			),
		},
		store: {
			heading: __(
				'Please share a few more details',
				'woocommerce-payments'
			),
			subheading: __(
				'This info will help us speed up the set up process.',
				'woocommerce-payments'
			),
		},
		loading: {
			heading: __(
				'One last step! Verify your identity with our partner',
				'woocommerce-payments'
			),
			subheading: __(
				'This will take place in a secure environment through our partner. Once your business details are verified, you’ll be redirected back to your store dashboard.',
				'woocommerce-payments'
			),
			cta: __(
				'Finish your verification process',
				'woocommerce-payments'
			),
		},
		embedded: {
			heading: __(
				'One last step! Verify your identity with our partner',
				'woocommerce-payments'
			),
			subheading: __(
				'This info will verify your account',
				'woocommerce-payments'
			),
		},
	},
	fields: {
		country: __(
			'Where is your business located?',
			'woocommerce-payments'
		),
		business_type: __(
			'What type of legal entity is your business?',
			'woocommerce-payments'
		),
		'company.structure': __(
			'What category of legal entity identify your business?',
			'woocommerce-payments'
		),
		mcc: __(
			'What type of goods or services does your business sell? ',
			'woocommerce-payments'
		),
		annual_revenue: __(
			'What is your estimated annual Ecommerce revenue (USD)?',
			'woocommerce-payments'
		),
		go_live_timeframe: __(
			'What is the estimated timeline for taking your store live?',
			'woocommerce-payments'
		),
	},
	errors: {
		generic: __( 'Please provide a response', 'woocommerce-payments' ),
		country: __( 'Please provide a country', 'woocommerce-payments' ),
		business_type: __(
			'Please provide a business type',
			'woocommerce-payments'
		),
		mcc: __(
			'Please provide a type of goods or services',
			'woocommerce-payments'
		),
	},
	placeholders: {
		generic: __( 'Select an option', 'woocommerce-payments' ),
		country: __( 'Select a country', 'woocommerce-payments' ),
		annual_revenue: __(
			'Select your annual revenue',
			'woocommerce-payments'
		),
		go_live_timeframe: __( 'Select a timeline', 'woocommerce-payments' ),
	},
	annualRevenues: {
		less_than_250k: __( 'Less than $250k', 'woocommerce-payments' ),
		from_250k_to_1m: __( '$250k - $1M', 'woocommerce-payments' ),
		from_1m_to_20m: __( '$1M - $20M', 'woocommerce-payments' ),
		from_20m_to_100m: __( '$20M - $100M', 'woocommerce-payments' ),
		more_than_100m: __( 'More than $100M', 'woocommerce-payments' ),
	},
	goLiveTimeframes: {
		already_live: __( 'My store is already live', 'woocommerce-payments' ),
		within_1month: __( 'Within 1 month', 'woocommerce-payments' ),
		from_1_to_3months: __( '1 – 3 months', 'woocommerce-payments' ),
		from_3_to_6months: __( '3 – 6 months', 'woocommerce-payments' ),
		more_than_6months: __( '6+ months', 'woocommerce-payments' ),
	},
	tos: interpolateComponents( {
		mixedString: sprintf(
			__(
				/* translators: %1$s: WooPayments, %2$s: WooPay  */
				'By using %1$s, you agree to be bound by our {{tosLink}}Terms of Service{{/tosLink}} (including {{merchantTermsLink}}%2$s merchant terms{{/merchantTermsLink}}) and acknowledge that you have read our {{privacyPolicyLink}}Privacy Policy{{/privacyPolicyLink}}.',
				'woocommerce-payments'
			),
			'WooPayments',
			'WooPay'
		),
		components: {
			tosLink: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					rel="external noopener noreferrer"
					target="_blank"
					href={ documentationUrls.tos }
				/>
			),
			merchantTermsLink: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					rel="external noopener noreferrer"
					target="_blank"
					href={ documentationUrls.merchantTerms }
				/>
			),
			privacyPolicyLink: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					rel="external noopener noreferrer"
					target="_blank"
					href={ documentationUrls.privacyPolicy }
				/>
			),
		},
	} ),
	inlineNotice: {
		title: __( 'Business Location:', 'woocommerce-payments' ),
		action: __( 'Change', 'woocommerce-payments' ),
	},
	continue: __( 'Continue', 'woocommerce-payments' ),
	back: __( 'Back', 'woocommerce-payments' ),
	cancel: __( 'Cancel', 'woocommerce-payments' ),
};
