/* eslint-disable max-len */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

export default {
	noThanks: __( 'No thanks', 'woocommerce' ),
	limitedTimeOffer: __( 'Limited time offer', 'woocommerce' ),
	TosAndPp: createInterpolateElement(
		__(
			'The discount will be applied to payments processed via WooPayments upon completion of installation, setup, and connection. ',
			'woocommerce'
		),
		{
			a1: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					href="https://wordpress.com/tos"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
			a2: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<a
					href="https://automattic.com/privacy/"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
		}
	),
	termsAndConditions: ( url: string ) =>
		createInterpolateElement(
			__(
				'*See <a>Terms and Conditions</a> for details.',
				'woocommerce'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content
					<a href={ url } target="_blank" rel="noopener noreferrer" />
				),
			}
		),
	paymentOptions: __(
		'WooPayments is pre-integrated with all popular payment options',
		'woocommerce'
	),
	learnMore: __( 'Learn more', 'woocommerce' ),
	survey: {
		title: __( 'No thanks, I don’t want WooPayments', 'woocommerce' ),
		intro: __(
			'Note that the extension hasn’t been installed, this will simply remove WooPayments from the navigation. Please take a moment to tell us why you’d like to dismiss WooPayments.',
			'woocommerce'
		),
		question: __(
			'Why would you like to dismiss the new payments experience?',
			'woocommerce'
		),
		happyLabel: __(
			'I’m already happy with my payments setup',
			'woocommerce'
		),
		installLabel: __(
			'I don’t want to install another plugin',
			'woocommerce'
		),
		moreInfoLabel: __(
			'I need more information about WooPayments',
			'woocommerce'
		),
		anotherTimeLabel: __(
			'I’m open to installing it another time',
			'woocommerce'
		),
		somethingElseLabel: __(
			'It’s something else (Please share below)',
			'woocommerce'
		),
		commentsLabel: __( 'Comments (Optional)', 'woocommerce' ),
		cancelButton: __( 'Just dismiss WooPayments', 'woocommerce' ),
		submitButton: __( 'Dismiss and send feedback', 'woocommerce' ),
	},
	faq: {
		haveQuestions: __( 'Have questions?', 'woocommerce' ),
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
				'Enable PayPal Payments alongside WooPayments. Give your customers another way to pay safely and conveniently via PayPal, PayLater, and Venmo.',
				'woocommerce'
			),
		},
		amazonpay: {
			title: __( 'Amazon Pay', 'woocommerce' ),
			description: __(
				'Enable Amazon Pay alongside WooPayments and give buyers the ability to pay via Amazon Pay. Transactions take place via Amazon embedded widgets, so the buyer never leaves your site.',
				'woocommerce'
			),
		},
		klarna: {
			title: __( 'Klarna', 'woocommerce' ),
			description: __(
				'Enable Klarna alongside WooPayments. With Klarna Payments buyers can choose the payment installment option they want, Pay Now, Pay Later, or Slice It. No credit card numbers, no passwords, no worries.',
				'woocommerce'
			),
		},
		affirm: {
			title: __( 'Affirm', 'woocommerce' ),
			description: __(
				'Enable Affirm alongside WooPayments and give buyers the ability to pick the payment option that works for them and their budget — from 4 interest-free payments every 2 weeks to monthly installments.',
				'woocommerce'
			),
		},
		installText: ( extensionsString: string ) => {
			const extensionsNumber = extensionsString.split( ', ' ).length;
			return createInterpolateElement(
				sprintf(
					/* translators: %s = names of the installed extensions */
					_n(
						'Installing <strong>WooPayments</strong> will automatically activate <strong>%s</strong> extension in your store.',
						'Installing <strong>WooPayments</strong> will automatically activate <strong>%s</strong> extensions in your store.',
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
