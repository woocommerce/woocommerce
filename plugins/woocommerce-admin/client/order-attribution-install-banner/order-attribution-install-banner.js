/**
 * External dependencies
 */
import { Button, Card, CardBody } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { recordEvent } from '@woocommerce/tracks';
import { getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import OrderAttributionInstallBannerImage from './order-attribution-install-banner-image';
import useOrderAttributionInstallBanner from './use-order-attribution-install-banner';
import './style.scss';

export const OrderAttributionInstallBanner = ( {
	bannerImage = <OrderAttributionInstallBannerImage />,
	eventContext = 'analytics-overview',
} ) => {
	const { isDismissed, dismiss, shouldShowBanner } =
		useOrderAttributionInstallBanner();

	useEffect( () => {
		if ( ! shouldShowBanner || isDismissed ) {
			return;
		}
		recordEvent( 'order_attribution_install_banner_viewed', {
			path: getPath(),
			context: eventContext,
		} );
	}, [ eventContext, shouldShowBanner, isDismissed ] );

	if ( ! shouldShowBanner || isDismissed ) {
		return null;
	}

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
					<div className="woocommerce-order-attribution-install-analytics-overview-banner__text-badge">
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
						{ __(
							'Understand what truly drives revenue with our powerful order attribution extension. Use it to track your sales journey, identify your most effective marketing channels, and optimize your sales strategy.',
							'woocommerce'
						) }
					</Text>
					<div>
						<Button
							href="https://woocommerce.com/products/woocommerce-analytics"
							variant="primary"
							onClick={ () =>
								recordEvent(
									'order_attribution_install_banner_clicked',
									{
										path: getPath(),
										context: eventContext,
									}
								)
							}
						>
							{ __( 'Try it now', 'woocommerce' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ () => dismiss( eventContext ) }
						>
							{ __( 'Dismiss', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</CardBody>
		</Card>
	);
};
