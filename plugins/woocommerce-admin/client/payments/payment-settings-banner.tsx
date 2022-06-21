/**
 * External dependencies
 */
import { Card, CardFooter, CardBody, Button } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';
import {
	ONBOARDING_STORE_NAME,
	PAYMENT_GATEWAYS_STORE_NAME,
	PaymentGateway,
	WCDataSelector,
} from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useExperiment } from '@woocommerce/explat';
import { getAdminLink } from '@woocommerce/settings';
import moment from 'moment';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';

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
import WCPayBannerImage from './wcpay-banner-image';
import './payment-recommendations.scss';
import { isWcPaySupported } from './utils';
import { getAdminSetting } from '~/utils/admin-settings';

export const PaymentMethodsIcons = () => (
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
	const WC_PAY_SETUP_URL = getAdminLink(
		'admin.php?wcpay-connect=1&_wpnonce=' +
			getAdminSetting( 'wcpay_welcome_page_connect_nonce' )
	);

	return (
		<Card size="medium" className="woocommerce-recommended-payments-banner">
			<CardBody className="woocommerce-recommended-payments-banner__body">
				<div className="woocommerce-recommended-payments-banner__image_container">
					<WCPayBannerImage />
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
						{ interpolateComponents( {
							mixedString: __(
								'By using WooCommerce Payments you agree to be bound by our {{tosLink}}Terms of Service{{/tosLink}} and acknowledge that you have read our {{privacyLink}}Privacy Policy{{/privacyLink}} ',
								'woocommerce'
							),
							components: {
								tosLink: (
									<Link
										href="https://wordpress.com/tos/"
										type="external"
										target="_blank"
									>
										<></>
									</Link>
								),
								privacyLink: (
									<Link
										href="https://automattic.com/privacy/"
										type="external"
										target="_blank"
									>
										<></>
									</Link>
								),
							},
						} ) }
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
					<PaymentMethodsIcons />
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

const DefaultPaymentMethodsHeaderText = () => (
	<>
		<h2>Payment Methods</h2>
		<div id="payment_gateways_options-description">
			<p>
				Installed payment methods are listed below and can be sorted to
				control their display order on the frontend.
			</p>
		</div>
	</>
);

export const PaymentsBannerWrapper = () => {
	const { installedPaymentGateways, paymentGatewaySuggestions, isResolving } =
		useSelect( ( select: WCDataSelector ) => {
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

	const isWcPayInstalled = installedPaymentGateways.some(
		( gateway: PaymentGateway ) => {
			return gateway.id === 'woocommerce_payments';
		}
	);

	const isWcPayDisabled = installedPaymentGateways.find(
		( gateway: PaymentGateway ) => {
			return (
				gateway.id === 'woocommerce_payments' &&
				gateway.enabled === false
			);
		}
	);

	const momentDate = moment().utc();
	const year = momentDate.format( 'YYYY' );
	const month = momentDate.format( 'MM' );
	const [ isLoadingExperiment, experimentAssignment ] = useExperiment(
		`woocommerce_payments_settings_banner_${ year }_${ month }`
	);
	if ( ! isResolving && ! isLoadingExperiment ) {
		if (
			isWcPaySupported( paymentGatewaySuggestions ) &&
			isWcPayInstalled &&
			isWcPayDisabled &&
			experimentAssignment?.variationName === 'treatment'
		) {
			return <WcPayBanner />;
		}
		return <DefaultPaymentMethodsHeaderText />;
	}
	return <DefaultPaymentMethodsHeaderText />;
};
