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
				'woocommerce'
			),
			subheading: __(
				'We’ll use these details to enable payments for your store. This information can’t be changed after your account is created.',
				'woocommerce'
			),
		},
		store: {
			heading: __( 'Please share a few more details', 'woocommerce' ),
			subheading: __(
				'This info will help us speed up the set up process.',
				'woocommerce'
			),
		},
		loading: {
			heading: __(
				'One last step! Verify your identity with our partner',
				'woocommerce'
			),
			subheading: __(
				'This will take place in a secure environment through our partner. Once your business details are verified, you’ll be redirected back to your store dashboard.',
				'woocommerce'
			),
			cta: __( 'Finish your verification process', 'woocommerce' ),
		},
		embedded: {
			heading: __(
				'One last step! Verify your identity with our partner',
				'woocommerce'
			),
			subheading: __(
				'This info will verify your account',
				'woocommerce'
			),
		},
	},
	fields: {
		country: __( 'Where is your business located?', 'woocommerce' ),
		business_type: __(
			'What type of legal entity is your business?',
			'woocommerce'
		),
		'company.structure': __(
			'What category of legal entity identify your business?',
			'woocommerce'
		),
		mcc: __(
			'What type of goods or services does your business sell? ',
			'woocommerce'
		),
		annual_revenue: __(
			'What is your estimated annual Ecommerce revenue (USD)?',
			'woocommerce'
		),
		go_live_timeframe: __(
			'What is the estimated timeline for taking your store live?',
			'woocommerce'
		),
	},
	errors: {
		generic: __( 'Please provide a response', 'woocommerce' ),
		country: __( 'Please provide a country', 'woocommerce' ),
		business_type: __( 'Please provide a business type', 'woocommerce' ),
		mcc: __( 'Please provide a type of goods or services', 'woocommerce' ),
	},
	placeholders: {
		generic: __( 'Select an option', 'woocommerce' ),
		country: __( 'Select a country', 'woocommerce' ),
		annual_revenue: __( 'Select your annual revenue', 'woocommerce' ),
		go_live_timeframe: __( 'Select a timeline', 'woocommerce' ),
	},
	annualRevenues: {
		less_than_250k: __( 'Less than $250k', 'woocommerce' ),
		from_250k_to_1m: __( '$250k - $1M', 'woocommerce' ),
		from_1m_to_20m: __( '$1M - $20M', 'woocommerce' ),
		from_20m_to_100m: __( '$20M - $100M', 'woocommerce' ),
		more_than_100m: __( 'More than $100M', 'woocommerce' ),
	},
	goLiveTimeframes: {
		already_live: __( 'My store is already live', 'woocommerce' ),
		within_1month: __( 'Within 1 month', 'woocommerce' ),
		from_1_to_3months: __( '1 – 3 months', 'woocommerce' ),
		from_3_to_6months: __( '3 – 6 months', 'woocommerce' ),
		more_than_6months: __( '6+ months', 'woocommerce' ),
	},
	tos: interpolateComponents( {
		mixedString: sprintf(
			/* translators: %1$s: WooPayments, %2$s: WooPay  */
			__(
				'By using %1$s, you agree to be bound by our {{tosLink}}Terms of Service{{/tosLink}} (including {{merchantTermsLink}}%2$s merchant terms{{/merchantTermsLink}}) and acknowledge that you have read our {{privacyPolicyLink}}Privacy Policy{{/privacyPolicyLink}}.',
				'woocommerce'
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
		title: __( 'Business Location:', 'woocommerce' ),
		action: __( 'Change', 'woocommerce' ),
	},
	continue: __( 'Continue', 'woocommerce' ),
	back: __( 'Back', 'woocommerce' ),
	cancel: __( 'Cancel', 'woocommerce' ),
};
