/**
 * External dependencies
 */
import { Card, CardFooter, CardBody, Button } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';
import {
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	Plugin,
	PaymentGateway,
	WCDataSelector,
} from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	Visa,
	MasterCard,
	Amex,
	ApplePay,
	Giropay,
	GooglePay,
	CB,
	DinersClub,
	Discover,
	UnionPay,
	JCB,
	Sofort,
} from '../payments-welcome/cards';
import Banner from './banner';
import './payment-recommendations.scss';

export const PaymentMethods = () => (
	<div className="woocommerce-recommended-payments-banner__footer_icon_container">
		<Visa />
		<MasterCard />
		<Amex />
		<ApplePay />
		<GooglePay />
		<CB />
		<Giropay />
		<DinersClub />
		<Discover />
		<UnionPay />
		<JCB />
		<Sofort />
	</div>
);

const WcPayBanner = () => {
	const WC_PAY_SETUP_URL =
		'./admin.php?page=wc-settings&tab=checkout&section=woocommerce_payments';
	return (
		<Card size="medium" className="woocommerce-recommended-payments-banner">
			<CardBody className="woocommerce-recommended-payments-banner__body">
				<div className="woocommerce-recommended-payments-banner__image_container">
					<Banner />
				</div>
				<div className="woocommerce-recommended-payments-banner__text_container">
					<Text
						variant="title.small"
						as="p"
						size="24"
						lineHeight="28px"
						padding="0 20px 0 0"
					>
						{ __(
							'Accept Payments and manage your business.',
							'woocommerce'
						) }
					</Text>
					<Text
						className={
							'woocommerce-recommended-payments__header-heading'
						}
						variant="caption"
						as="p"
						size="12"
						lineHeight="16px"
					>
						{ __(
							'By using WooCommerce Payments you agree to be bound by our Terms of Service and acknowledge that you have read our Privacy Policy',
							'woocommerce'
						) }
					</Text>
					<Button href={ WC_PAY_SETUP_URL } isPrimary>
						{ __( 'Get started', 'woocommerce' ) }
					</Button>
				</div>
			</CardBody>
			<CardFooter className="woocommerce-recommended-payments-banner__footer">
				<div>
					<Text variant="caption" as="p" size="12" lineHeight="16px">
						{ __(
							'Accepted payment methods include:',
							'woocommerce'
						) }
					</Text>
				</div>
				<div>
					<PaymentMethods />
				</div>
				<div>
					<Text variant="caption" as="p" size="12" lineHeight="16px">
						{ __( '& more.', 'woocommerce' ) }
					</Text>
				</div>
			</CardFooter>
		</Card>
	);
};

export const PaymentsRecommendationsBanner = () => {
	const {
		installedPaymentGateways,
		paymentGatewaySuggestions,
		isResolving,
	} = useSelect( ( select: WCDataSelector ) => {
		return {
			installedPaymentGateways: select(
				PAYMENT_GATEWAYS_STORE_NAME
			).getPaymentGateways(),
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getPaymentGatewaySuggestions'
			),
			paymentGatewaySuggestions: select(
				ONBOARDING_STORE_NAME
			).getPaymentGatewaySuggestions(),
		};
	} );

	const supportsWCPayments =
		paymentGatewaySuggestions &&
		paymentGatewaySuggestions.filter(
			( paymentGatewaySuggestion: Plugin ) => {
				return (
					paymentGatewaySuggestion.id.indexOf(
						'woocommerce_payments'
					) === 0
				);
			}
		).length === 1;

	const isWcPayInstalled = installedPaymentGateways.some(
		( gateway: PaymentGateway ) => {
			return gateway.id === 'woocommerce_payments';
		}
	);

	const isWcPayEnabled = installedPaymentGateways.find(
		( gateway: PaymentGateway ) => {
			return (
				gateway.id === 'woocommerce_payments' &&
				gateway.enabled === true
			);
		}
	);

	const isWcPayBannerExplat = true;
	if ( ! isResolving ) {
		if (
			supportsWCPayments &&
			isWcPayInstalled &&
			! isWcPayEnabled &&
			isWcPayBannerExplat
		) {
			// add tracks event here
			return <WcPayBanner />;
		}
		// add tracks event here because we want to know when the control is shown
	}
	return null;
};
