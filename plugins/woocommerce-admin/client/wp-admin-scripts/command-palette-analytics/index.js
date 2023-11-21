/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { chartBar } from '@wordpress/icons';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useCommandWithTracking } from '../command-palette/use-command-with-tracking';

const useWooCommerceAnalyticsCommand = ( { label, path } ) => {
	useCommandWithTracking( {
		name: `woocommerce${ path }`,
		label,
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
	useWooCommerceAnalyticsCommand( {
		label: __( 'Products Analytics', 'woocommerce' ),
		path: '/analytics/products',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Revenue Analytics', 'woocommerce' ),
		path: '/analytics/revenue',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Orders Analytics', 'woocommerce' ),
		path: '/analytics/orders',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Variations Analytics', 'woocommerce' ),
		path: '/analytics/variations',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Categories Analytics', 'woocommerce' ),
		path: '/analytics/categories',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Coupons Analytics', 'woocommerce' ),
		path: '/analytics/coupons',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Taxes Analytics', 'woocommerce' ),
		path: '/analytics/taxes',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Downloads Analytics', 'woocommerce' ),
		path: '/analytics/downloads',
	} );
	useWooCommerceAnalyticsCommand( {
		label: __( 'Stock Analytics', 'woocommerce' ),
		path: '/analytics/stock',
	} );

	return null;
};

registerPlugin( 'woocommerce-analytics-commands-registration', {
	render: WooCommerceAnalyticsCommands,
} );
