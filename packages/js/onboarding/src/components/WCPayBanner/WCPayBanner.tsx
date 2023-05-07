/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardFooter, CardBody } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { PaymentMethodsIcons } from './PaymentMethodsIcons';
import { WCPayBannerImage } from './WCPayBannerImage';

export const WCPayBannerFooter: React.VFC = () => (
	<CardFooter className="woocommerce-recommended-payments-banner__footer">
		<div>
			<Text variant="caption" as="p" size="12" lineHeight="16px">
				{ __( 'Accepted payment methods include:', 'woocommerce' ) }
			</Text>
		</div>
		<PaymentMethodsIcons />
	</CardFooter>
);

export const WCPayBannerText: React.VFC< {
	actionButton: React.ReactNode;
} > = ( { actionButton } ) => {
	return (
		<div className="woocommerce-recommended-payments-banner__text_container">
			<Text
				className="woocommerce-recommended-payments__header-title"
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
				className="woocommerce-recommended-payments__header-heading"
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
			{ actionButton }
		</div>
	);
};

export const WCPayBannerBody: React.VFC< {
	textPosition: 'left' | 'right';
	actionButton: React.ReactNode;
	bannerImage?: React.ReactNode;
} > = ( {
	actionButton,
	textPosition,
	bannerImage = <WCPayBannerImage />,
} ) => {
	return (
		<CardBody className="woocommerce-recommended-payments-banner__body">
			{ textPosition === 'left' ? (
				<>
					<WCPayBannerText actionButton={ actionButton } />
					<div className="woocommerce-recommended-payments-banner__image_container">
						{ bannerImage }
					</div>
				</>
			) : (
				<>
					<div className="woocommerce-recommended-payments-banner__image_container">
						{ bannerImage }
					</div>
					<WCPayBannerText actionButton={ actionButton } />
				</>
			) }
		</CardBody>
	);
};

export const WCPayBanner: React.FC = ( { children } ) => {
	return (
		<Card size="medium" className="woocommerce-recommended-payments-banner">
			{ children }
		</Card>
	);
};
