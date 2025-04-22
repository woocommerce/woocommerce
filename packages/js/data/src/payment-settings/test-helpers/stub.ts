/**
 * Internal dependencies
 */
import {
	OfflinePaymentMethodProvider,
	PaymentProvider,
	PaymentProviderType,
	SuggestedPaymentExtension,
	SuggestedPaymentExtensionCategory,
} from '../types';

export const providersStub: PaymentProvider[] = [
	{
		id: '_wc_pes_paypal_full_stack',
		_order: 2,
		_type: PaymentProviderType.Suggestion,
		title: 'PayPal Payments',
		description:
			'Safe and secure payments using credit cards or your customer&#039;s PayPal account.',
		short_description: '',
		image: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/paypal.png',
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/payment_methods/72x72/paypal.png',
		links: [
			{
				_type: 'about',
				url: 'https://woocommerce.com/products/woocommerce-paypal-payments/',
			},
			{
				_type: 'terms',
				url: 'https://www.paypal.com/legalhub/home',
			},
			{
				_type: 'support',
				url: 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-paypal-payments',
			},
		],
		tags: [ 'made_in_woo', 'preferred' ],
		plugin: {
			_type: 'wporg',
			slug: 'woocommerce-paypal-payments',
			file: 'woocommerce-paypal-payments/woocommerce-paypal-payments',
			status: 'installed',
		},
		_links: {
			hide: {
				href: 'http://localhost:8082/wp-json/wc-admin/settings/payments/suggestion/paypal_full_stack/hide',
			},
		},
	},
	{
		id: 'woocommerce_payments',
		_order: 2,
		_type: PaymentProviderType.Gateway,
		title: 'WooPayments',
		description:
			'WooPayments gives your store flexibility to accept credit cards, debit cards, and Apple Pay. Enable popular local payment methods and other digital wallets like Google Pay to give customers even more choice.',
		supports: [
			'products',
			'refunds',
			'tokenization',
			'add_payment_method',
		],
		state: {
			enabled: false,
			account_connected: false,
			needs_setup: false,
			test_mode: true,
			dev_mode: false,
		},
		management: {
			_links: {
				settings: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-settings&tab=checkout&section=woocommerce_payments',
				},
			},
		},
		onboarding: {
			state: {
				started: false,
				completed: false,
				test_mode: true,
			},
			_links: {
				onboard: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-admin&path=/payments/onboarding',
				},
			},
			recommended_payment_methods: [
				{
					id: 'card',
					_order: 1,
					title: 'Card',
					description: 'Card payments.',
					icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/woopayments.svg',
					enabled: true,
					extraTitle: 'Extra title',
					extraDescription: 'Extra description.',
					extraIcon:
						'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/extra-icon.svg',
				},
				{
					id: 'woopay',
					_order: 2,
					title: 'WooPay',
					description: 'WooPay checkout.',
					icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/woopayments.svg',
					enabled: true,
					extraTitle: 'Extra title',
					extraDescription: 'Extra description.',
					extraIcon:
						'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/extra-icon.svg',
				},
			],
		},
		image: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/woopayments.svg',
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/woopayments.svg',
		links: [
			{
				_type: 'pricing',
				url: 'https://woocommerce.com/document/woopayments/fees-and-debits/',
			},
			{
				_type: 'about',
				url: 'https://woocommerce.com/payments/',
			},
			{
				_type: 'terms',
				url: 'https://woocommerce.com/document/woopayments/our-policies/',
			},
			{
				_type: 'documentation',
				url: 'https://woocommerce.com/document/woopayments/',
			},
			{
				_type: 'support',
				url: 'https://woocommerce.com/my-account/contact-support/?select=woopayments',
			},
		],
		tags: [ 'made_in_woo', 'preferred' ],
		plugin: {
			_type: 'wporg',
			slug: 'woocommerce-payments',
			file: 'woocommerce-payments/woocommerce-payments',
			status: 'active',
		},
	},
	{
		id: '_wc_offline_payment_methods_group',
		_order: 3,
		_type: PaymentProviderType.OfflinePmsGroup,
		title: 'Offline Payment Methods',
		description: 'Allow shoppers to pay offline.',
		plugin: {
			_type: 'wporg',
			slug: 'woocommerce', // This is always WooCommerce for offline payment methods group.
			file: '', // This is always empty for offline payment methods group.
			status: 'active',
		},
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/payment_methods/cod.svg',
	},
];

export const offlinePaymentGatewaysStub: OfflinePaymentMethodProvider[] = [
	{
		id: 'bacs',
		_type: PaymentProviderType.OfflinePm,
		_order: 999,
		title: 'Direct bank transfer',
		description:
			'Take payments in person via BACS. More commonly known as direct bank/wire transfer.',
		supports: [ 'products' ],
		state: {
			enabled: true,
			account_connected: true,
			needs_setup: false,
			test_mode: false,
			dev_mode: false,
		},
		management: {
			_links: {
				settings: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs',
				},
			},
		},
		onboarding: {
			state: {
				started: true,
				completed: true,
				test_mode: false,
			},
			_links: {
				onboard: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs',
				},
			},
		},
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/payment_methods/bacs.svg',
		plugin: {
			_type: 'wporg',
			slug: 'woocommerce',
			file: 'woocommerce/woocommerce',
			status: 'active',
		},
	},
	{
		id: 'cheque',
		_type: PaymentProviderType.OfflinePm,
		_order: 1000,
		title: 'Cheque payments',
		description:
			'Take payments in person via cheques. This offline gateway can also be useful to test purchases.',
		supports: [ 'products' ],
		state: {
			enabled: true,
			account_connected: true,
			needs_setup: false,
			test_mode: false,
			dev_mode: false,
		},
		management: {
			_links: {
				settings: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cheque',
				},
			},
		},
		onboarding: {
			state: {
				started: true,
				completed: true,
				test_mode: false,
			},
			_links: {
				onboard: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cheque',
				},
			},
		},
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/payment_methods/cheque.svg',
		plugin: {
			_type: 'wporg',
			slug: 'woocommerce',
			file: 'woocommerce/woocommerce',
			status: 'active',
		},
	},
	{
		id: 'cod',
		_type: PaymentProviderType.OfflinePm,
		_order: 1001,
		title: 'Cash on delivery',
		description:
			'Let your shoppers pay upon delivery — by cash or other methods of payment.',
		supports: [ 'products' ],
		state: {
			enabled: true,
			account_connected: true,
			needs_setup: false,
			test_mode: false,
			dev_mode: false,
		},
		management: {
			_links: {
				settings: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cod',
				},
			},
		},
		onboarding: {
			state: {
				started: true,
				completed: true,
				test_mode: false,
			},
			_links: {
				onboard: {
					href: 'http://localhost:8082/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cod',
				},
			},
		},
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/payment_methods/cod.svg',
		plugin: {
			_type: 'wporg',
			slug: 'woocommerce',
			file: 'woocommerce/woocommerce',
			status: 'active',
		},
	},
];

export const suggestionsStub: SuggestedPaymentExtension[] = [
	{
		id: 'airwallex',
		_priority: 40,
		_type: 'psp',
		title: 'Airwallex payments',
		description:
			'Boost international sales and save on FX fees. Accept 60+ local payment methods, including Apple Pay and Google Pay.',
		plugin: {
			_type: 'wporg',
			slug: 'airwallex-online-payments-gateway',
			file: 'airwallex-online-payments-gateway/airwallex-online-payments-gateway',
			status: 'not_installed',
		},
		image: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/airwallex.png',
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/payment_methods/72x72/airwallex.png',
		short_description: '',
		links: [
			{
				_type: 'pricing',
				url: 'https://www.airwallex.com/pricing',
			},
			{
				_type: 'about',
				url: 'https://www.airwallex.com/',
			},
			{
				_type: 'terms',
				url: 'https://www.airwallex.com/terms/',
			},
			{
				_type: 'documentation',
				url: 'https://www.airwallex.com/docs/payments__plugins__woocommerce__install-the-woocommerce-plugin',
			},
			{
				_type: 'support',
				url: 'https://help.airwallex.com/',
			},
		],
		tags: [],
		category: 'psp',
	},
	{
		id: 'square_in_person',
		_priority: 50,
		_type: 'psp',
		title: 'Square',
		description:
			'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). Sell online and in store and track sales and inventory in one place.',
		plugin: {
			_type: 'wporg',
			slug: 'woocommerce-square',
			file: 'woocommerce-square/woocommerce-square',
			status: 'not_installed',
		},
		image: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/onboarding/square-black.png',
		icon: 'http://localhost:8082/wp-content/plugins/woocommerce/assets/images/payment_methods/72x72/square.png',
		short_description: '',
		links: [
			{
				_type: 'about',
				url: 'https://woocommerce.com/products/square/',
			},
			{
				_type: 'documentation',
				url: 'https://woocommerce.com/document/woocommerce-square/',
			},
			{
				_type: 'support',
				url: 'https://woocommerce.com/my-account/contact-support/?select=square',
			},
		],
		tags: [ 'made_in_woo' ],
		category: 'psp',
	},
];

export const suggestionCategoriesStub: SuggestedPaymentExtensionCategory[] = [
	{
		id: 'express_checkout',
		_priority: 10,
		title: 'Express Checkouts',
		description:
			'Allow shoppers to fast-track the checkout process with express options like Apple Pay and Google Pay.',
	},
	{
		id: 'bnpl',
		_priority: 20,
		title: 'Buy Now, Pay Later',
		description: 'Offer flexible payment options to your shoppers.',
	},
	{
		id: 'psp',
		_priority: 30,
		title: 'Payment Providers',
		description: 'Give your shoppers additional ways to pay.',
	},
];
