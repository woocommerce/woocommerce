/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { fireEvent, render, screen } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
/**
 * Internal dependencies
 */

import { PaymentGatewaySuggestions } from '../index';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn().mockImplementation( () => ( {
		updatePaymentGateway: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

const paymentGatewaySuggestions = [
	{
		id: 'stripe',
		title: 'Stripe',
		content:
			'Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay.',
		image: 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/stripe.png',
		plugins: [ 'woocommerce-gateway-stripe' ],
		is_visible: true,
		recommendation_priority: 3,
		category_other: [ 'US' ],
		category_additional: [],
	},
	{
		id: 'ppcp-gateway',
		title: 'PayPal Payments',
		content:
			"Safe and secure payments using credit cards or your customer's PayPal account.",
		image: 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/paypal.png',
		plugins: [ 'woocommerce-paypal-payments' ],
		is_visible: true,
		category_other: [ 'US' ],
		category_additional: [ 'US' ],
	},
	{
		id: 'cod',
		title: 'Cash on delivery',
		content: 'Take payments in cash upon delivery.',
		image: 'http://localhost:8888/wp-content/plugins/woocommerce-admin/images/onboarding/cod.svg',
		is_visible: true,
		is_offline: true,
	},
	{
		id: 'bacs',
		title: 'Direct bank transfer',
		content: 'Take payments via bank transfer.',
		image: 'http://localhost:8888/wp-content/plugins/woocommerce-admin/images/onboarding/bacs.svg',
		is_visible: true,
		is_offline: true,
	},
	{
		id: 'woocommerce_payments:non-us',
		title: 'WooPayments',
		content:
			'Manage transactions without leaving your WordPress Dashboard. Only with WooPayments.',
		image: 'http://localhost:8888/wp-content/plugins/woocommerce-admin/images/onboarding/wcpay.svg',
		plugins: [ 'woocommerce-payments' ],
		description:
			'With WooPayments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies. Track cash flow and manage recurring revenue directly from your store’s dashboard - with no setup costs or monthly fees.',
		is_visible: true,
		recommendation_priority: 1,
	},
	{
		id: 'woocommerce_payments:bnpl',
		title: 'Activate BNPL instantly on WooPayments',
		content:
			'The world’s favorite buy now, pay later options and many more are right at your fingertips with WooPayments — all from one dashboard, without needing multiple extensions and logins.',
		image: 'http://localhost:8888/wp-content/plugins/woocommerce-admin/images/onboarding/wcpay-bnpl.svg',
		plugins: [ 'woocommerce-payments' ],
		is_visible: true,
		recommendation_priority: 1,
	},
	{
		id: 'eway',
		title: 'Eway',
		content:
			'The Eway extension for WooCommerce allows you to take credit card payments directly on your store without redirecting your customers to a third party site to make payment.',
		image: 'http://localhost:8888/wp-content/plugins/woocommerce-admin/images/onboarding/eway.png',
		plugins: [ 'woocommerce-gateway-eway' ],
		is_visible: true,
		category_other: [ 'US' ],
		category_additional: [ 'US' ],
	},
];

const paymentGatewaySuggestionsWithoutWCPay = paymentGatewaySuggestions.filter(
	( p ) => ! p.id.startsWith( 'woocommerce_payments' )
);

describe( 'PaymentGatewaySuggestions', () => {
	test( 'should render all payment gateways, including WCPay', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions,
			countryCode: 'US',
			installedPaymentGateways: [],
		} ) );

		const { container } = render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		const paymentTitleElements = container.querySelectorAll(
			'.woocommerce-task-payment__title'
		);

		const paymentTitles = Array.from( paymentTitleElements ).map(
			( e ) => e.textContent
		);

		expect( paymentTitles ).toEqual( [
			'Stripe',
			'PayPal Payments',
			'Eway',
			'Cash on delivery',
			'Direct bank transfer',
		] );

		expect(
			container
				.querySelector(
					'.woocommerce-recommended-payments-banner__footer'
				)
				.textContent.includes( 'WooPayments' )
		).toBe( true );

		// WCPay BNPL suggestion should not be shown since WCPay is shown.
		expect(
			container.querySelector( '.woocommerce-wcpay-bnpl-suggestion' )
		).toBeFalsy();
	} );

	test( 'should render all payment gateways except WCPay', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions: paymentGatewaySuggestionsWithoutWCPay,
			countryCode: 'US',
			installedPaymentGateways: [],
		} ) );

		const { container } = render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		expect(
			screen.getByText( 'Choose a payment provider' )
		).toBeInTheDocument();

		const paymentTitleElements = container.querySelectorAll(
			'.woocommerce-task-payment__title > span:first-child'
		);

		const paymentTitles = Array.from( paymentTitleElements ).map(
			( e ) => e.textContent
		);

		expect( paymentTitles ).toEqual( [
			'Stripe',
			'PayPal Payments',
			'Eway',
			'Cash on delivery',
			'Direct bank transfer',
		] );
	} );

	test( 'should render the payment gateway offline options at the bottom', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions: paymentGatewaySuggestionsWithoutWCPay,
			installedPaymentGateways: [],
		} ) );

		const { container } = render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		const paymentTitles = container.querySelectorAll(
			'.woocommerce-task-payment__title'
		);

		expect( paymentTitles[ paymentTitles.length - 1 ].textContent ).toBe(
			'Direct bank transfer'
		);
	} );

	test( 'should have finish setup button for installed payment gateways', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions: paymentGatewaySuggestionsWithoutWCPay,
			countryCode: 'US',
			installedPaymentGateways: [
				{
					id: 'ppcp-gateway',
					title: 'PayPal Payments',
					content:
						"Safe and secure payments using credit cards or your customer's PayPal account.",
					image: 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/paypal.png',
					plugins: [ 'woocommerce-paypal-payments' ],
					is_visible: true,
					settings_url: 'http://example.com',
				},
			],
		} ) );

		const { getByText } = render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		expect( getByText( 'Finish setup' ) ).toBeInTheDocument();
	} );

	test( 'should show "category_additional" gateways and WCPay BNPL after WCPay is set up', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions,
			installedPaymentGateways: [
				{
					id: 'woocommerce_payments',
					title: 'WooPayments',
					plugins: [ 'woocommerce-payments' ],
					is_visible: true,
					needs_setup: false,
					settings_url: 'http://example.com',
				},
			],
			countryCode: 'US', // Country with WCPay BNPL.
		} ) );

		const { container } = render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		expect(
			screen.getByText( 'Additional payment options' )
		).toBeInTheDocument();

		const paymentTitleElements = container.querySelectorAll(
			'.woocommerce-task-payment__title'
		);

		const paymentTitles = Array.from( paymentTitleElements ).map(
			( e ) => e.textContent
		);

		expect( paymentTitles ).toEqual( [
			'PayPal Payments',
			'Eway',
			'Cash on delivery',
			'Direct bank transfer',
		] );

		// WCPay BNPL suggestion should be shown.
		expect(
			container.querySelector( '.woocommerce-wcpay-bnpl-suggestion' )
		).toBeInTheDocument();
	} );

	test( 'should show "category_additional" gateways after a primary gateway (other than WCPay) is set up', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions,
			installedPaymentGateways: [
				{
					id: 'ppcp-gateway',
					title: 'PayPal Payments',
					content:
						"Safe and secure payments using credit cards or your customer's PayPal account.",
					image: 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/paypal.png',
					plugins: [ 'woocommerce-paypal-payments' ],
					is_visible: true,
					settings_url: 'http://example.com',
				},
			],
			countryCode: 'US',
		} ) );

		const { container } = render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		expect(
			screen.getByText( 'Additional payment options' )
		).toBeInTheDocument();

		const paymentTitleElements = container.querySelectorAll(
			'.woocommerce-task-payment__title'
		);

		const paymentTitles = Array.from( paymentTitleElements ).map(
			( e ) => e.textContent
		);

		expect( paymentTitles ).toEqual( [
			'PayPal PaymentsSetup required',
			'Eway',
			'Cash on delivery',
			'Direct bank transfer',
		] );
	} );

	test( 'should record event correctly when finish setup is clicked', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions: paymentGatewaySuggestionsWithoutWCPay,
			countryCode: 'US',
			installedPaymentGateways: [
				{
					id: 'ppcp-gateway',
					title: 'PayPal Payments',
					content:
						"Safe and secure payments using credit cards or your customer's PayPal account.",
					image: 'http://localhost:8888/wp-content/plugins/woocommerce/assets/images/paypal.png',
					plugins: [ 'woocommerce-paypal-payments' ],
					is_visible: true,
					settings_url: 'http://example.com',
				},
			],
		} ) );

		render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		fireEvent.click( screen.getByText( 'Finish setup' ) );
		expect( recordEvent ).toHaveBeenCalledWith( 'tasklist_payment_setup', {
			selected: 'ppcp_gateway',
		} );
	} );

	test( 'should record event correctly when see more is clicked', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions,
			installedPaymentGateways: [],
			countryCode: 'US',
		} ) );

		render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		fireEvent.click( screen.getByText( 'Other payment providers' ) );
		fireEvent.click( screen.getByText( 'See more' ) );
		expect(
			recordEvent.mock.calls[ recordEvent.mock.calls.length - 1 ]
		).toEqual( [ 'tasklist_payment_see_more', {} ] );
	} );

	test( 'should record event correctly when WCPay BNPL Get started is clicked', () => {
		const onComplete = jest.fn();
		const query = {};
		useSelect.mockImplementation( () => ( {
			isResolving: false,
			getPaymentGateway: jest.fn(),
			paymentGatewaySuggestions,
			installedPaymentGateways: [
				{
					id: 'woocommerce_payments',
					title: 'WooPayments',
					plugins: [ 'woocommerce-payments' ],
					is_visible: true,
					needs_setup: false,
					settings_url: 'http://example.com',
				},
			],
			countryCode: 'US', // Country with WCPay BNPL.
		} ) );

		const { container } = render(
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		);

		fireEvent.click(
			container.querySelector(
				'.woocommerce-wcpay-bnpl-suggestion__button'
			)
		);
		expect(
			recordEvent.mock.calls[ recordEvent.mock.calls.length - 1 ]
		).toEqual( [ 'tasklist_payments_wcpay_bnpl_click' ] );
	} );
} );
