/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { plugins } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useOrderAttributionInstallBanner } from '~/order-attribution-install-banner';
import './style.scss';

export const HeaderBanner = () => {
	const eventContext = 'analytics-overview-header';
	const { isDismissed, shouldShowBanner } =
		useOrderAttributionInstallBanner();
	const showHeaderBanner = shouldShowBanner && isDismissed;

	useEffect( () => {
		if ( ! showHeaderBanner ) {
			return;
		}
		recordEvent( 'order_attribution_install_banner_viewed', {
			path: getPath(),
			context: eventContext,
		} );
	}, [ showHeaderBanner ] );

	if ( ! showHeaderBanner ) {
		return null;
	}

	return (
		<Button
			className="woocommerce-order-attribution-install-header-banner"
			href="https://woocommerce.com/products/woocommerce-analytics"
			variant="secondary"
			icon={ plugins }
			size="default"
			onClick={ () =>
				recordEvent( 'order_attribution_install_banner_clicked', {
					path: getPath(),
					context: eventContext,
				} )
			}
		>
			{ __( 'Try Order Attribution', 'woocommerce' ) }
		</Button>
	);
};
