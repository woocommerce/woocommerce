/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { Button, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import OrderAttributionInstallBannerImage from './order-attribution-install-banner-image';
import useOrderAttributionInstallBanner from './use-order-attribution-install-banner';
import './style.scss';

export const OrderAttributionInstallBanner = ( {
	bannerImage = <OrderAttributionInstallBannerImage />,
} ) => {
	const {
		isOrderAttributionInstallBannerDismissed,
		dismissOrderAttributionInstallBanner,
		shouldShowBanner,
	} = useOrderAttributionInstallBanner();

	if ( ! shouldShowBanner || isOrderAttributionInstallBannerDismissed ) {
		return null;
	}

	recordEvent( 'order_attribution_install_banner_viewed' );

	return (
		<Card
			size="medium"
			className="woocommerce-order-attribution-install-analytics-overview-banner"
		>
			<CardBody className="woocommerce-order-attribution-install-analytics-overview-banner__body">
				<div className="woocommerce-order-attribution-install-analytics-overview-banner__image_container">
					{ bannerImage }
				</div>
				<div className="woocommerce-order-attribution-install-analytics-overview-banner__text_container">
					<div className="woocommerce-order-attribution-install-analytics-overview-banner__text-chip">
						<Text
							className="woocommerce-order-attribution-install-analytics-overview-banner__text-description"
							as="p"
							size="12"
							align="center"
						>
							{ __( 'New', 'woocommerce' ) }
						</Text>
					</div>
					<Text
						className="woocommerce-order-attribution-install-analytics-overview-banner__text-title"
						as="p"
						size="16"
					>
						{ __(
							'Discover what drives your sales',
							'woocommerce'
						) }
					</Text>
					<Text
						className="woocommerce-order-attribution-install-analytics-overview-banner__text-description"
						as="p"
						size="12"
					>
						{ createInterpolateElement(
							__(
								'Understand what truly drives revenue with our powerful order attribution extension. Use it to track<br/>your sales journey, identify your most effective marketing channels, and optimize your sales strategy.',
								'woocommerce'
							),
							{ br: <br /> }
						) }
					</Text>
					<div>
						<Button
							href="https://woocommerce.com/products/woocommerce-analytics"
							variant="primary"
							onClick={ () =>
								recordEvent(
									'order_attribution_install_banner_clicked'
								)
							}
						>
							{ __( 'Try it now', 'woocommerce' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ dismissOrderAttributionInstallBanner }
						>
							{ __( 'Dismiss', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</CardBody>
		</Card>
	);
};
