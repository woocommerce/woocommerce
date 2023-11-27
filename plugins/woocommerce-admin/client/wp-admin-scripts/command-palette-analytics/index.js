/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { chartBar } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { registerCommandWithTracking } from '../command-palette/register-command-with-tracking';

const registerWooCommerceAnalyticsCommand = ( { label, path } ) => {
	registerCommandWithTracking( {
		name: `woocommerce${ path }`,
		label: sprintf(
			// translators: %s is the title of the Analytics Page. This is used as a command in the Command Palette.
			__( 'WooCommerce Analytics: %s', 'woocommerce' ),
			label
		),
		icon: chartBar,
		callback: () => {
			document.location = addQueryArgs( 'admin.php', {
				page: 'wc-admin',
				path,
			} );
		},
	} );
};

const WooCommerceAnalyticsCommands = () => {
	if (
		window.hasOwnProperty( 'wcCommandPaletteAnalytics' ) &&
		window.wcCommandPaletteAnalytics.hasOwnProperty( 'reports' ) &&
		Array.isArray( window.wcCommandPaletteAnalytics.reports )
	) {
		const analyticsReports = window.wcCommandPaletteAnalytics.reports;

		analyticsReports.forEach( ( analyticsReport ) => {
			registerWooCommerceAnalyticsCommand( {
				label: analyticsReport.title,
				path: analyticsReport.path,
			} );
		} );
	}

	return null;
};

registerPlugin( 'woocommerce-analytics-commands-registration', {
	render: WooCommerceAnalyticsCommands,
} );
