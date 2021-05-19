/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const extensionBenefits = [
	{
		slug: 'mailpoet',
		title: __(
			'All-in-one email marketing by MailPoet',
			'woocommerce-admin'
		),
		icon: 'onboarding/mailpoet.png',
		description: __(
			'Create and send purchase follow-up emails, newsletters, and promotional campaigns straight from your dashboard.',
			'woocommerce-admin'
		),
	},
	{
		slug: 'facebook-for-woocommerce',
		title: __( 'Market on Facebook', 'woocommerce-admin' ),
		icon: 'onboarding/fb-woocommerce.png',
		description: __(
			'Grow your business by targeting the right people and driving sales with Facebook.',
			'woocommerce-admin'
		),
	},
	{
		slug: 'google-listings-and-ads',
		title: __(
			'Drive traffic to your store with Google Listings and Ads',
			'woocommerce-admin'
		),
		icon: 'onboarding/g-shopping.png',
		description: __(
			'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
			'woocommerce-admin'
		),
	},
	{
		slug: 'mailchimp-for-woocommerce',
		title: __( 'Contact customers with Mailchimp', 'woocommerce-admin' ),
		icon: 'onboarding/mailchimp.png',
		description: __(
			'Send targeted campaigns, recover abandoned carts and much more with Mailchimp.',
			'woocommerce-admin'
		),
	},
	{
		slug: 'creative-mail-by-constant-contact',
		title: __(
			'Email marketing for WooCommerce with Creative Mail',
			'woocommerce-admin'
		),
		icon: 'onboarding/creativemail.png',
		description: __(
			'Create on-brand store campaigns, fast email promotions and customer retargeting with Creative Mail.',
			'woocommerce-admin'
		),
	},
];
