/* eslint-disable max-len */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

export default {
	button: __( 'Install', 'woocommerce-admin' ),
	nothanks: __( 'No thanks', 'woocommerce-admin' ),
	limitedTimeOffer: __( 'Limited time offer', 'woocommerce-admin' ),
	heading: __( 'WooCommerce Payments', 'woocommerce-admin' ),
	bannerHeading: __(
		'Save big with WooCommerce Payments',
		'woocommerce-admin'
	),
	bannerCopy: __(
		'50% transaction fee discount for up to $125,000 in payments or six months',
		'woocommerce-admin'
	),
	discountCopy: __(
		'Discount will be applied upon install and completed setup of WooCommerce Payments',
		'woocommerce-admin'
	),
	learnMore: __( 'Learn more', 'woocommerce-admin' ),

	onboarding: {
		description: __(
			"Save 50% on transaction fees by managing transactions with WooCommerce Payments. With WooCommerce Payments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies. Track cash flow and manage recurring revenue directly from your store's dashboard - with no setup costs or monthly fees.",
			'woocommerce-admin'
		),
	},

	paymentMethodsHeading: __(
		'Accepted payment methods',
		'woocommerce-admin'
	),
	surveyTitle: __( 'Remove WooCommerce Payments', 'woocommerce-admin' ),

	surveyIntro: createInterpolateElement(
		// Note: \xa0 is used to create a non-breaking space.
		__(
			'Please take a moment to tell us why you’d like to remove WooCommerce Payments. This will remove WooCommerce\xa0Payments from the navigation. You can enable it again in <strong>WooCommerce\xa0Settings\xa0>\xa0Payments</strong>, however the promotion will not apply.',
			'woocommerce-admin'
		),
		{
			strong: <strong />,
		}
	),

	surveyQuestion: __(
		'What made you disable the new payments experience?',
		'woocommerce-admin'
	),

	surveyHappyLabel: __(
		'I’m already happy with my payments setup',
		'woocommerce-admin'
	),

	surveyInstallLabel: __(
		'I don’t want to install another plugin',
		'woocommerce-admin'
	),

	surveyMoreInfoLabel: __(
		'I need more information about WooCommerce Payments',
		'woocommerce-admin'
	),

	surveyAnotherTimeLabel: __(
		'I’m open to installing it another time',
		'woocommerce-admin'
	),

	surveySomethingElseLabel: __(
		'It’s something else (Please share below)',
		'woocommerce-admin'
	),

	surveyCommentsLabel: __( 'Comments (Optional)', 'woocommerce-admin' ),

	surveyCancelButton: __(
		'Just remove WooCommerce Payments',
		'woocommerce-admin'
	),

	surveySubmitButton: __( 'Remove and send feedback', 'woocommerce-admin' ),

	terms: createInterpolateElement(
		__(
			'By clicking "Install", you agree to the <a>Terms of Service</a>',
			'woocommerce-admin'
		),
		{
			a: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					href="https://wordpress.com/tos"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
		}
	),

	faq: {
		faqHeader: __( 'Frequently asked questions', 'woocommerce-admin' ),

		question1: __( 'What is WooCommerce Payments?', 'woocommerce-admin' ),

		question1Answer1: __(
			'WooCommerce Payments is an integrated payment solution, built by WooCommerce, for WooCommerce. Use WooCommerce Payments to manage your payments, track cash flow, and manage revenue from your dashboard.',
			'woocommerce-admin'
		),

		question1Answer2: __(
			'You can securely accept credit and debit card payments, Apple Pay, bank transfers, recurring revenue, accelerated checkout, and more - in over 100+ currencies with WooCommerce Payments.',
			'woocommerce-admin'
		),

		question2: __(
			'Can I use WooCommerce Payments alongside other payment gateways?',
			'woocommerce-admin'
		),

		question2Answer1: __(
			'Yes. WooCommerce Payments works alongside other payment service providers, including Stripe, PayPal, and all others. We’ve built it with this flexibility in mind so that you can ensure your store is working to meet your business needs.',
			'woocommerce-admin'
		),

		question3: __(
			'Why should I choose WCPay over other payment gateways?',
			'woocommerce-admin'
		),

		question3Answer1: __(
			"Native dashboard: track cash flow from the same WordPress dashboard you're already using to manage product catalogue, inventory, orders, fulfilment, and otherwise run your online storefront.",
			'woocommerce-admin'
		),

		question3Answer2: __(
			'In-person payments: the only payment method that helps you sell online and offline with the official WooCommerce mobile app instead of a paid POS solution.',
			'woocommerce-admin'
		),

		question3Answer3: __(
			'Subscription functionality: offer customers a recurring option for your inventory without purchasing an additional extension.',
			'woocommerce-admin'
		),

		question3Answer4: __(
			'Native multi-currency: increase sales by making it easier for customers outside your country to purchase from your store, without an extension.',
			'woocommerce-admin'
		),

		question4: __(
			'How will I save money using WooCommerce Payments?',
			'woocommerce-admin'
		),

		// eslint-disable-next-line @wordpress/i18n-translator-comments
		question4Answer1: __(
			'Stores accepted into the promotional program will receive a 100% discount on transaction fees (excluding currency conversion fees) for the first $25,000 in payments, or 3 months, whichever comes first. Simply install the extension and if eligible you’ll be entered into the promotional offer.',
			'woocommerce-admin'
		),

		question4Answer2: __(
			'To be eligible for this promotional offer, your store must: (1) meet the WooCommerce Payments usage requirements; (2) be a U.S.-based business; (3) not have processed payments through WooCommerce Payments before; and (4) be accepted into the promotional program.',
			'woocommerce-admin'
		),

		question5: __(
			'What are the fees for WooCommerce Payments?',
			'woocommerce-admin'
		),

		question5Answer1: __(
			'WooCommerce Payments uses a pay-as-you-go pricing model. You pay only for activity on the account. No setup fee or monthly fee. Fees differ based on the country of your account and country of your customer’s card.',
			'woocommerce-admin'
		),

		question5Answer2: createInterpolateElement(
			__( '<a>View all fees</a>', 'woocommerce-admin' ),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://docs.woocommerce.com/document/payments/faq/fees/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question5Answer7: createInterpolateElement(
			// eslint-disable-next-line @wordpress/i18n-translator-comments
			__(
				'1.5% fee on the payout amount for <a>instant deposits</a>',
				'woocommerce-admin'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://docs.woocommerce.com/document/payments/instant-deposits/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question5Answer8: createInterpolateElement(
			__( '<a>View all fees</a>', 'woocommerce-admin' ),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://docs.woocommerce.com/document/payments/faq/fees/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question6: __(
			'When will I receive deposits for my WooCommerce Payments account balance?',
			'woocommerce-admin'
		),

		question6Answer1: createInterpolateElement(
			__(
				'For most accounts, <a>WooCommerce Payments</a> automatically pays out your available account balance into your nominated account daily after a standard pending period.',
				'woocommerce-admin'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://woocommerce.com/payments/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question6Answer2: __(
			'Payments received each day become part of the pending balance. That pending balance will become available after a pending period. On the day it becomes available, it will be automatically paid out to your bank account. The pending period is based on the country of the account.',
			'woocommerce-admin'
		),

		question6Answer3: __(
			'For example, a business based in New Zealand has a pending period of 4 business days. Payments made to this account on Wednesday will be paid out on the next Tuesday.',
			'woocommerce-admin'
		),

		question6Answer4: __(
			'Most banks will reflect the deposit in your account as soon as they receive the transfer from WooCommerce Payments. Some may take a few extra days to make the balance available to you.',
			'woocommerce-admin'
		),

		question6Answer5: createInterpolateElement(
			__( '<a>All deposits details</a>', 'woocommerce-admin' ),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://docs.woocommerce.com/document/payments/faq/deposit-schedule/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question7: __(
			'What products are not permitted on my store when accepting payments with WooCommerce Payments?',
			'woocommerce-admin'
		),

		question7Answer1: __(
			'Due to restrictions from card networks, our payment service providers, and their financial service providers, some businesses and product types that are not allowed to transact using WooCommerce Payments, including but not limited to:',
			'woocommerce-admin'
		),

		question7Answer2: __(
			'Virtual currency, including video game or virtual world credits',
			'woocommerce-admin'
		),

		question7Answer3: __( 'Counterfeit goods', 'woocommerce-admin' ),

		question7Answer4: __(
			'Adult content and services',
			'woocommerce-admin'
		),

		question7Answer5: __(
			'Drug paraphernalia (including e-cigarette, vapes and nutraceuticals)',
			'woocommerce-admin'
		),

		question7Answer6: __( 'Multi-level marketing', 'woocommerce-admin' ),

		question7Answer7: __( 'Pseudo pharmaceuticals', 'woocommerce-admin' ),

		question7Answer8: __(
			'Social media activity, like Twitter followers, Facebook likes, YouTube views',
			'woocommerce-admin'
		),

		question7Answer9: __(
			'Substances designed to mimic illegal drugs',
			'woocommerce-admin'
		),

		question7Answer10: __( 'Firearms, ammunition', 'woocommerce-admin' ),

		question7Answer11: createInterpolateElement(
			__(
				'The full list of these businesses can be found in <a>Stripe’s Restricted Businesses list</a>.',
				'woocommerce-admin'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://stripe.com/restricted-businesses"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question7Answer12: __(
			'By signing up to use WooCommerce Payments, you agree not to accept payments in connection with these restricted activities, practices or products. We also work to ensure that no prohibited activity is conducted on WooCommerce Payments.',
			'woocommerce-admin'
		),

		question7Answer13: __(
			'If we become aware of prohibited activity, we may restrict or shutdown the account responsible.',
			'woocommerce-admin'
		),

		question8: __(
			'Can WooCommerce Payments support Subscriptions and recurring payment options?',
			'woocommerce-admin'
		),

		question8Answer1: createInterpolateElement(
			__(
				'WooCommerce Payments supports charging automatic recurring payments via the <a>WooCommerce Subscriptions</a> plugin.',
				'woocommerce-admin'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://woocommerce.com/products/woocommerce-subscriptions/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question8Answer2: __(
			'WooCommerce Payments offers full compatibility with WooCommerce Subscriptions’ features, including:',
			'woocommerce-admin'
		),

		question8Answer3: __( 'Subscription suspension', 'woocommerce-admin' ),

		question8Answer4: __(
			'Subscription cancellation',
			'woocommerce-admin'
		),

		question8Answer5: __(
			'Subscription reactivation',
			'woocommerce-admin'
		),

		question8Answer6: __( 'Multiple subscriptions', 'woocommerce-admin' ),

		question8Answer7: __( 'Recurring total changes', 'woocommerce-admin' ),

		question8Answer8: __( 'Payment date changes', 'woocommerce-admin' ),

		question8Answer9: __(
			'Customer & Store Manager payment method changes',
			'woocommerce-admin'
		),

		question8Answer10: createInterpolateElement(
			__(
				'For more details on the subscription features that WooCommerce Payments offers, refer to the <a>subscription section of the WooCommerce Payments start up guide</a>.',
				'woocommerce-admin'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://docs.woocommerce.com/document/payments/#subscriptions"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		haveMoreQuestions: __( 'Have more questions?', 'woocommerce-admin' ),

		getInTouch: __( 'Get in touch', 'woocommerce-admin' ),
	},
};
