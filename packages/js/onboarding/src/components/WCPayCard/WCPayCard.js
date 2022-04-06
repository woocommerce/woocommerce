/**
 * External dependencies
 */
import { Card, CardBody, CardFooter } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { createElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WCPayAcceptedMethods } from '../WCPayAcceptedMethods';
import WCPayHeroImage from '../../images/wcpay-hero-image';
import WCPayBenefit1 from '../../images/wcpay-benefit-1';
import WCPayBenefit2 from '../../images/wcpay-benefit-2';
import WCPayBenefit3 from '../../images/wcpay-benefit-3';

export const WCPayCardBody = ( {
	heading,
	children,
	onLinkClick = () => {},
} ) => (
	<CardBody>
		<div className="hstack">
			<div className="vstack content-center flex-1">
				{ heading && (
					<Text
						as="h1"
						className="woocommerce-task-payment-wcpay__heading"
					>
						{ heading }
					</Text>
				) }
				<Text
					className="woocommerce-task-payment-wcpay__description"
					as="p"
					lineHeight="1.5em"
				>
					{ interpolateComponents( {
						mixedString: __(
							'By using WooCommerce Payments you agree to be bound by our {{tosLink /}} and acknowledge that you have read our {{privacyLink /}}',
							'woocommerce'
						),
						components: {
							tosLink: (
								<Link
									target="_blank"
									type="external"
									rel="noreferrer"
									href="https://woocommerce.com/payments/?utm_medium=product"
									onClick={ onLinkClick }
								>
									{ __( 'Terms of Service', 'woocommerce' ) }
								</Link>
							),
							privacyLink: (
								<Link
									target="_blank"
									type="external"
									rel="noreferrer"
									href="https://woocommerce.com/payments/?utm_medium=product"
									onClick={ onLinkClick }
								>
									{ __( 'Privacy Policy', 'woocommerce' ) }
								</Link>
							),
						},
					} ) }
					<br />
				</Text>
				<div>{ children }</div>
			</div>
			<div>
				<WCPayHeroImage className="wcpay-hero-image" />
			</div>
		</div>
	</CardBody>
);

export const WCPayCardFooter = () => (
	<CardFooter>
		<WCPayAcceptedMethods />
	</CardFooter>
);

export const WCPayCard = ( { children } ) => {
	return (
		<Card className="woocommerce-task-payment-wcpay" size="large">
			{ children }
		</Card>
	);
};

export const WCPayBenefitCard = () => {
	return (
		<Card className="woocommerce-task-payment-wcpay" size="large">
			<CardBody>
				<div className="hstack content-around">
					<div className="woocommerce-task-payment-wcpay__benefit vstack">
						<WCPayBenefit1 />
						{ __(
							'Offer your customers their preferred way to pay including debit and credit card payments, Apple Pay, Sofort, SEPA, iDeal and many more.',
							'woocommerce'
						) }
					</div>
					<div className="woocommerce-task-payment-wcpay__benefit vstack">
						<WCPayBenefit2 />
						{ __(
							'Sell to international markets and accept more than 135 currencies with local payment methods.',
							'woocommerce'
						) }
					</div>
					<div className="woocommerce-task-payment-wcpay__benefit vstack">
						<WCPayBenefit3 />
						{ __(
							'Earn and manage recurring revenue and get automatic deposits into your nominated bank account.',
							'woocommerce'
						) }
					</div>
				</div>
			</CardBody>
		</Card>
	);
};
