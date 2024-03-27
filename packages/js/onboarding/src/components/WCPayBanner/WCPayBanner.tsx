/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardFooter, CardBody } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { PaymentMethodsIcons } from './PaymentMethodsIcons';
import { WCPayBannerImage } from './WCPayBannerImage';

export const WCPayBannerFooter: React.VFC< {
	isWooPayEligible: boolean;
} > = ( { isWooPayEligible } ) => (
	<CardFooter className="woocommerce-recommended-payments-banner__footer">
		<div>
			<Text variant="caption" as="p" size="12" lineHeight="16px">
				{ __(
					'WooPayments is pre-integrated with popular payment options:',
					'woocommerce'
				) }
			</Text>
		</div>
		<PaymentMethodsIcons isWooPayEligible={ isWooPayEligible } />
	</CardFooter>
);

export const WCPayBannerText: React.VFC< {
	actionButton: React.ReactNode;
	isWooPayEligible: boolean;
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
					'Payments made simple, designed exclusively for WooCommerce stores.',
					'woocommerce'
				) }
			</Text>
			{ actionButton }
		</div>
	);
};

export const WCPayBannerBody: React.VFC< {
	textPosition: 'left' | 'right';
	actionButton: React.ReactNode;
	bannerImage?: React.ReactNode;
	isWooPayEligible: boolean;
} > = ( {
	actionButton,
	textPosition,
	bannerImage = <WCPayBannerImage />,
	isWooPayEligible,
} ) => {
	return (
		<CardBody className="woocommerce-recommended-payments-banner__body">
			{ textPosition === 'left' ? (
				<>
					<WCPayBannerText
						actionButton={ actionButton }
						isWooPayEligible={ isWooPayEligible }
					/>
					<div className="woocommerce-recommended-payments-banner__image_container">
						{ bannerImage }
					</div>
				</>
			) : (
				<>
					<div className="woocommerce-recommended-payments-banner__image_container">
						{ bannerImage }
					</div>
					<WCPayBannerText
						actionButton={ actionButton }
						isWooPayEligible={ isWooPayEligible }
					/>
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
