import { Card, CardHeader, CardFooter, Button } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { EllipsisMenu, List, Pill } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import Banner from './banner';
import { PaymentMethods } from '../payments-welcome';

/**
 * Internal dependencies
 */
import './payment-recommendations.scss';
import { CardBody } from '@wordpress/components';

export const PaymentsRecommendationsBanner = () => {
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
					<Button isPrimary>
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
					<PaymentMethods className="woocommerce-recommended-payments-banner__footer_icon_container" />
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
