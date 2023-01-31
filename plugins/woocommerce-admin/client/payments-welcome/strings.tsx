/* eslint-disable max-len */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

export default {
	button: __( 'Install', 'woocommerce' ),
	nothanks: __( 'No thanks', 'woocommerce' ),
	limitedTimeOffer: __( 'Limited time offer', 'woocommerce' ),
	heading: __( 'WooCommerce Payments', 'woocommerce' ),
	bannerHeading: __( 'Payments, made simple', 'woocommerce' ),
	// eslint-disable-next-line @wordpress/i18n-translator-comments
	bannerCopy: createInterpolateElement(
		__(
			'<b>20% off payment processing</b><br/>on up to $1,000,000 USD in payments or over the next 6 months, whichever comes first.*',
			'woocommerce'
		),
		{
			b: <b />,
			br: <br />,
		}
	),
	discountCopy: __(
		'Discount will be applied to payments processed via WooCommerce Payments upon completion of installation, setup, and onboarding of WooCommerce Payments.',
		'woocommerce'
	),
	termsAndConditions: createInterpolateElement(
		__(
			'*See <a>Terms and Conditions</a> for more details.',
			'woocommerce'
		),
		{
			a: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					href="https://woocommerce.com/terms-conditions/woocommerce-payments-promotion-2022/"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
		}
	),
	learnMore: __( 'Learn more', 'woocommerce' ),

	onboarding: {
		description: __(
			'Run your business and manage your payments all in one place with the only solution built and supported by WooCommerce. With WooCommerce Payments you can collect payments, handle disputes, and track revenue from inside your store’s dashboard – with no setup costs or monthly fees.',
			'woocommerce'
		),
	},

	paymentMethodsHeading: __(
		'Give your buyers more ways to pay',
		'woocommerce'
	),
	surveyTitle: __(
		'No thanks, I don’t want WooCommerce Payments',
		'woocommerce'
	),

	surveyIntro: __(
		'Note that the extension hasn’t been installed, this will simply remove WooCommerce Payments from the navigation. Please take a moment to tell us why you’d like to dismiss WooCommerce Payments.',
		'woocommerce'
	),

	surveyQuestion: __(
		'Why would you like to dismiss the new payments experience?',
		'woocommerce'
	),

	surveyHappyLabel: __(
		'I’m already happy with my payments setup',
		'woocommerce'
	),

	surveyInstallLabel: __(
		'I don’t want to install another plugin',
		'woocommerce'
	),

	surveyMoreInfoLabel: __(
		'I need more information about WooCommerce Payments',
		'woocommerce'
	),

	surveyAnotherTimeLabel: __(
		'I’m open to installing it another time',
		'woocommerce'
	),

	surveySomethingElseLabel: __(
		'It’s something else (Please share below)',
		'woocommerce'
	),

	surveyCommentsLabel: __( 'Comments (Optional)', 'woocommerce' ),

	surveyCancelButton: __(
		'Just dismiss WooCommerce Payments',
		'woocommerce'
	),

	surveySubmitButton: __( 'Dismiss and send feedback', 'woocommerce' ),

	terms: createInterpolateElement(
		__(
			'By clicking "Install", you agree to the <a>Terms of Service</a>',
			'woocommerce'
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
		faqHeader: __( 'Frequently asked questions', 'woocommerce' ),

		question1: __( 'What is WooCommerce Payments?', 'woocommerce' ),

		question1Answer1: __(
			'Run your business and manage your payments all in one place with the only solution built and supported by WooCommerce.',
			'woocommerce'
		),

		question1Answer2: __(
			'Collect payments, handle disputes, and track revenue from inside your store’s dashboard. You can also securely accept credit and debit card payments, Apple Pay, local payment methods, recurring revenue, accelerated checkout, and more – in over 100+ currencies with WooCommerce Payments.',
			'woocommerce'
		),

		question2: __(
			'Can I use WooCommerce Payments alongside other payment gateways?',
			'woocommerce'
		),

		question2Answer1: __(
			'Yes. WooCommerce Payments works alongside other payment service providers, including Stripe, PayPal, and all others. We’ve built it with this flexibility in mind so that you can ensure your store is working to meet your business needs.',
			'woocommerce'
		),

		question3: __(
			'Why should I choose WooCommerce Payments over other payment gateways?',
			'woocommerce'
		),

		question3Answer1: __(
			"Native dashboard: track cash flow from the same WordPress dashboard you're already using to manage product catalog, inventory, orders, fulfillment, and otherwise run your online storefront.",
			'woocommerce'
		),

		question3Answer2: __(
			'In-person payments: WooCommerce Payments enables you to sell online and offline with the official WooCommerce mobile app instead of a paid POS solution (US and Canada only).',
			'woocommerce'
		),

		question3Answer3: __(
			'Subscription functionality: offer customers a recurring option for your inventory without purchasing an additional extension (US only).',
			'woocommerce'
		),

		question3Answer4: __(
			'Native multi-currency: multi-currency support built right in. Increase sales by making it easier for customers outside your country to purchase from your store, in local currencies, without another extension.',
			'woocommerce'
		),

		question3Answer5: __(
			'Maximize checkout conversion by providing buyers the payment methods that are most popular and relevant to them including credit & debit cards, Apple Pay, Google Pay, and local options based on their country. WooCommerce Payments gives your store more flexibility and your customers more purchasing power – including compatibility with extensions to add options like PayPal, Amazon Pay, as well as installment and buy now, pay later options like Affirm, Klarna, and Afterpay.',
			'woocommerce'
		),

		question4: __(
			'How will I save money using WooCommerce Payments?',
			'woocommerce'
		),

		question4Answer1: createInterpolateElement(
			// eslint-disable-next-line @wordpress/i18n-translator-comments
			__(
				'Stores accepted into the promotional program will receive a 20% discount on total payment processing costs on the first $1,000,000 USD of payment processing volume conducted using WooCommerce Payments, for up to 6 months from the date your store is accepted into the program. Learn more about eligibility for the promotional program and its terms and conditions <a>here</a>.',
				'woocommerce'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://woocommerce.com/terms-conditions/woocommerce-payments-promotion-2022/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question5: __(
			'What are the fees for WooCommerce Payments?',
			'woocommerce'
		),

		question5Answer1: __(
			'WooCommerce Payments uses a pay-as-you-go pricing model. You pay only for activity on the account. No setup fee or monthly fee. Fees differ based on the country of your account, the form of payment the buyer chooses, and the country of your customers card.',
			'woocommerce'
		),

		question5Answer2: createInterpolateElement(
			__( '<a>View all fees</a>', 'woocommerce' ),
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
			'woocommerce'
		),

		question6Answer1: createInterpolateElement(
			__(
				'For most accounts, WooCommerce Payments <a>automatically pays out</a> your available account balance into your nominated account daily after a standard pending period.',
				'woocommerce'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://woocommerce.com/document/payments/faq/deposit-schedule/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question6Answer2: __(
			'Payments received each day become part of the pending balance. That pending balance will become available after a pending period. On the day it becomes available, it will be automatically paid out to your bank account. The pending period is based on the country of the account.',
			'woocommerce'
		),

		question6Answer3: __(
			'For example, a business based in New Zealand has a pending period of 4 business days. Payments made to this account on Wednesday will be paid out on the next Tuesday.',
			'woocommerce'
		),

		question6Answer4: __(
			'Most banks will reflect the deposit in your account as soon as they receive the transfer from WooCommerce Payments. Some may take a few extra days to make the balance available to you.',
			'woocommerce'
		),

		question7: __(
			'What products are not permitted on my store when accepting payments with WooCommerce Payments?',
			'woocommerce'
		),

		question7Answer1: __(
			'Due to restrictions from card networks, our payment service providers, and their financial service providers, some businesses and product types that are not allowed to transact using WooCommerce Payments, including but not limited to:',
			'woocommerce'
		),

		question7Answer2: __(
			'Virtual currency, including video game or virtual world credits',
			'woocommerce'
		),

		question7Answer3: __( 'Counterfeit goods', 'woocommerce' ),

		question7Answer4: __( 'Adult content and services', 'woocommerce' ),

		question7Answer5: __(
			'Drug paraphernalia (including e-cigarette, vapes and nutraceuticals)',
			'woocommerce'
		),

		question7Answer6: __( 'Multi-level marketing', 'woocommerce' ),

		question7Answer7: __( 'Pseudo pharmaceuticals', 'woocommerce' ),

		question7Answer8: __(
			'Social media activity, like Twitter followers, Facebook likes, YouTube views',
			'woocommerce'
		),

		question7Answer9: __(
			'Substances designed to mimic illegal drugs',
			'woocommerce'
		),

		question7Answer10: __( 'Firearms, ammunition', 'woocommerce' ),

		question7Answer11: createInterpolateElement(
			__(
				'The full list of these businesses can be found in <a>Stripe’s Restricted Businesses list</a>.',
				'woocommerce'
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
			'woocommerce'
		),

		question7Answer13: __(
			'If we become aware of prohibited activity, we may restrict or shutdown the account responsible.',
			'woocommerce'
		),

		question8: __(
			'Can WooCommerce Payments support Subscriptions and recurring payment options?',
			'woocommerce'
		),

		question8Answer1: createInterpolateElement(
			__(
				'WooCommerce Payments supports charging automatic recurring payments via <a1>built-in subscription functionality</a1> for some stores or the <a2>WooCommerce Subscriptions</a2> plugin for all other stores.',
				'woocommerce'
			),
			{
				a1: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://woocommerce.com/document/payments/subscriptions/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
				a2: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://woocommerce.com/products/woocommerce-subscriptions/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		question8Answer2: createInterpolateElement(
			__(
				'For more details on which subscription offering is right for your store, refer to the <a>guide comparing subscription options</a>.',
				'woocommerce'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a
						href="https://woocommerce.com/document/payments/subscriptions/comparison/"
						target="_blank"
						rel="noopener noreferrer"
					/>
				),
			}
		),

		haveMoreQuestions: __( 'Have more questions?', 'woocommerce' ),

		getInTouch: __( 'Get in touch', 'woocommerce' ),
	},
	apms: {
		addMoreWaysToPay: __(
			'Add more ways for buyers to pay',
			'woocommerce'
		),
		seeMore: __( 'See more', 'woocommerce' ),
		paypal: {
			title: __( 'PayPal Payments', 'woocommerce' ),
			description: __(
				'Enable PayPal Payments alongside WooCommerce Payments. Give your customers another way to pay safely and conveniently via PayPal, PayLater, and Venmo.',
				'woocommerce'
			),
		},
		amazonpay: {
			title: __( 'Amazon Pay', 'woocommerce' ),
			description: __(
				'Enable Amazon Pay alongside WooCommerce Payments and give buyers the ability to pay via Amazon Pay. Transactions take place via Amazon embedded widgets, so the buyer never leaves your site.',
				'woocommerce'
			),
		},
		klarna: {
			title: __( 'Klarna', 'woocommerce' ),
			description: __(
				'Enable Klarna alongside WooCommerce Payments. With Klarna Payments buyers can choose the payment installment option they want, Pay Now, Pay Later, or Slice It. No credit card numbers, no passwords, no worries.',
				'woocommerce'
			),
		},
		affirm: {
			title: __( 'Affirm', 'woocommerce' ),
			description: __(
				'Enable Affirm alongside WooCommerce Payments and give buyers the ability to pick the payment option that works for them and their budget — from 4 interest-free payments every 2 weeks to monthly installments.',
				'woocommerce'
			),
		},
		installText: ( extensionsString: string ) => {
			const extensionsNumber = extensionsString.split( ', ' ).length;
			return createInterpolateElement(
				sprintf(
					/* translators: %s = names of the installed extensions */
					_n(
						'Installing <strong>WooCommerce Payments</strong> will automatically activate <strong>%s</strong> extension in your store.',
						'Installing <strong>WooCommerce Payments</strong> will automatically activate <strong>%s</strong> extensions in your store.',
						extensionsNumber,
						'woocommerce'
					),
					extensionsString
				),
				{
					strong: <strong />,
				}
			);
		},
		installTextPost: __( 'extension in your store.', 'woocommerce' ),
	},
};
