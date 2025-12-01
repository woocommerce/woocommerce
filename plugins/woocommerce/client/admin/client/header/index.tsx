/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { getScreenFromPath, isWCAdmin, getPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	LaunchYourStoreStatus,
	useLaunchYourStore,
} from '../launch-your-store';
import {
	OrderAttributionInstallBanner,
	BANNER_TYPE_HEADER as ORDER_ATTRIBUTION_INSTALL_BANNER_TYPE_HEADER,
} from '~/order-attribution-install-banner';
import { isTaskListActive } from '~/hooks/use-tasklists-state';
import { BaseHeader } from './shared';

export const PAGE_TITLE_FILTER = 'woocommerce_admin_header_page_title';

export const Header = ( {
	sections,
	query,
}: {
	sections: string[];
	query: Record< string, string >;
} ) => {
	const siteTitle = getSetting( 'siteTitle', '' );

	useEffect( () => {
		const documentTitle = sections
			.map( ( section: string | string[] ) => {
				return Array.isArray( section ) ? section[ 1 ] : section;
			} )
			.reverse()
			.join( ' &lsaquo; ' );

		const decodedTitle = decodeEntities(
			sprintf(
				/* translators: 1: document title. 2: page title */
				__( '%1$s &lsaquo; %2$s &#8212; WooCommerce', 'woocommerce' ),
				documentTitle,
				siteTitle
			)
		);

		if ( document.title !== decodedTitle ) {
			document.title = decodedTitle;
		}
	}, [ sections, siteTitle ] );

	const isHomescreen =
		isWCAdmin() && getScreenFromPath() === 'homescreen' && ! query.task;
	const { isLoading, launchYourStoreEnabled } = useLaunchYourStore( {
		enabled: isHomescreen,
	} );
	const showLaunchYourStoreStatus =
		isHomescreen && launchYourStoreEnabled && ! isLoading;

	const isAnalyticsOverviewScreen =
		isWCAdmin() && getPath() === '/analytics/overview';

	const showReminderBar = Boolean( isTaskListActive( 'setup' ) );

	return (
		<BaseHeader
			isEmbedded={ false }
			sections={ sections }
			query={ query }
			showReminderBar={ showReminderBar }
			leftAlign={ ! showLaunchYourStoreStatus }
		>
			{ showLaunchYourStoreStatus && <LaunchYourStoreStatus /> }
			{ isAnalyticsOverviewScreen && (
				// @ts-expect-error OrderAttributionInstallBanner is not typed
				<OrderAttributionInstallBanner
					bannerType={ ORDER_ATTRIBUTION_INSTALL_BANNER_TYPE_HEADER }
					eventContext="analytics-overview-header"
				/>
			) }
		</BaseHeader>
	);
};
