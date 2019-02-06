/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';

const SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';

const defaultOrderStatuses = [
	'completed',
	'processing',
	'refunded',
	'cancelled',
	'failed',
	'pending',
	'on-hold',
];
const orderStatuses = Object.keys( wcSettings.orderStatuses )
	.filter( status => status !== 'refunded' )
	.map( key => {
		return {
			value: key,
			label: wcSettings.orderStatuses[ key ],
			description: sprintf(
				__( 'Exclude the %s status from reports', 'wc-admin' ),
				wcSettings.orderStatuses[ key ]
			),
		};
	} );

export const analyticsSettings = applyFilters( SETTINGS_FILTER, [
	{
		name: 'woocommerce_excluded_report_order_statuses',
		label: __( 'Excluded Statuses:', 'wc-admin' ),
		inputType: 'checkboxGroup',
		options: [
			{
				key: 'defaultStatuses',
				options: orderStatuses.filter( status => defaultOrderStatuses.includes( status.value ) ),
			},
			{
				key: 'customStatuses',
				label: __( 'Custom Statuses', 'wc-admin' ),
				options: orderStatuses.filter( status => ! defaultOrderStatuses.includes( status.value ) ),
			},
		],
		helpText: interpolateComponents( {
			mixedString: __(
				'Orders with these statuses are excluded from the totals in your reports. ' +
					'The {{strong}}Refunded{{/strong}} status can not be excluded.  {{moreLink}}Learn more{{/moreLink}}',
				'wc-admin'
			),
			components: {
				strong: <strong />,
				moreLink: <Link href="#" type="external" />, // @todo This needs to be replaced with a real link.
			},
		} ),
		initialValue: wcSettings.wcAdminSettings.woocommerce_excluded_report_order_statuses || [],
		defaultValue: [ 'pending', 'cancelled', 'failed' ],
	},
] );
