/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const extensionBenefits = [
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
	{
		slug: 'kliken-marketing-for-google',
		title: __(
			'Drive traffic to your store with Google Ads & Marketing by Kliken',
			'woocommerce-admin'
		),
		icon: 'onboarding/g-shopping.png',
		description: __(
			'Get in front of shoppers and drive traffic so you can grow your business with Smart Shopping Campaigns and free listings.',
			'woocommerce-admin'
		),
	},
];
