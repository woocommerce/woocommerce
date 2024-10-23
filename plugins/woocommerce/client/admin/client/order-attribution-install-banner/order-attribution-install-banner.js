/**
 * External dependencies
 */
import { Button, Card, CardBody } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { plugins } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { recordEvent } from '@woocommerce/tracks';
import { getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import OrderAttributionInstallBannerImage from './order-attribution-install-banner-image';
import { useOrderAttributionInstallBanner } from './use-order-attribution-install-banner';
import './style.scss';

const WC_ANALYTICS_PRODUCT_URL =
	'https://woocommerce.com/products/woocommerce-analytics';

export const OrderAttributionInstallBanner = ( {
	bannerImage = <OrderAttributionInstallBannerImage />,
	eventContext = 'analytics-overview',
	isHeaderBanner = false,
	showBadge = true,
	dismissable = true,
	title = 'Discover what drives your sales',
	description = 'Understand what truly drives revenue with our powerful order attribution extension. Use it to track your sales journey, identify your most effective marketing channels, and optimize your sales strategy.',
} ) => {
	const { isDismissed, dismiss, shouldShowBanner } =
		useOrderAttributionInstallBanner();

	const shouldRender =
		( isHeaderBanner && shouldShowBanner && isDismissed ) ||
		( ! isHeaderBanner && shouldShowBanner && ! isDismissed );

	const onButtonClick = () => {
		recordEvent( 'order_attribution_install_banner_clicked', {
			path: getPath(),
			context: eventContext,
		} );
	};

	useEffect( () => {
		if ( ! shouldRender ) {
			return;
		}
		recordEvent( 'order_attribution_install_banner_viewed', {
			path: getPath(),
			context: eventContext,
		} );
	}, [ eventContext, shouldRender ] );

	if ( ! shouldRender ) {
		return null;
	}

	if ( isHeaderBanner ) {
		return (
			<Button
				className="woocommerce-order-attribution-install-header-banner"
				href={ WC_ANALYTICS_PRODUCT_URL }
				variant="secondary"
				icon={ plugins }
				size="default"
				onClick={ onButtonClick }
			>
				{ __( 'Try Order Attribution', 'woocommerce' ) }
			</Button>
		);
	}

	if ( title ) {
		// translators: %s: The dynamic title.
		title = sprintf( __( '%s ', 'woocommerce' ), title );
	}

	if ( description ) {
		// translators: %s: The dynamic description.
		description = sprintf( __( '%s ', 'woocommerce' ), description );
	}

	return (
		<Card
			size="medium"
			className="woocommerce-order-attribution-install-banner"
		>
			<CardBody className="woocommerce-order-attribution-install-banner__body">
				<div className="woocommerce-order-attribution-install-banner__image_container">
					{ bannerImage }
				</div>
				<div className="woocommerce-order-attribution-install-banner__text_container">
					{ showBadge && (
						<div className="woocommerce-order-attribution-install-banner__text-badge">
							<Text
								className="woocommerce-order-attribution-install-banner__text-description"
								as="p"
								size="12"
								align="center"
							>
								{ __( 'New', 'woocommerce' ) }
							</Text>
						</div>
					) }
					{ title && (
						<Text
							className="woocommerce-order-attribution-install-banner__text-title"
							as="p"
							size="16"
						>
							{ title }
						</Text>
					) }
					{ description && (
						<Text
							className="woocommerce-order-attribution-install-banner__text-description"
							as="p"
							size="12"
						>
							{ description }
						</Text>
					) }
					<div>
						<Button
							href={ WC_ANALYTICS_PRODUCT_URL }
							variant="primary"
							onClick={ onButtonClick }
						>
							{ __( 'Try it now', 'woocommerce' ) }
						</Button>
						{ dismissable && (
							<Button
								variant="tertiary"
								onClick={ () => dismiss( eventContext ) }
							>
								{ __( 'Dismiss', 'woocommerce' ) }
							</Button>
						) }
					</div>
				</div>
			</CardBody>
		</Card>
	);
};
