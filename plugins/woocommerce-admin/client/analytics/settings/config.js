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

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES } from 'wc-api/constants';

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
				__( 'Exclude the %s status from reports', 'woocommerce-admin' ),
				wcSettings.orderStatuses[ key ]
			),
		};
	} );

export const analyticsSettings = applyFilters( SETTINGS_FILTER, [
	{
		name: 'woocommerce_excluded_report_order_statuses',
		label: __( 'Excluded Statuses:', 'woocommerce-admin' ),
		inputType: 'checkboxGroup',
		options: [
			{
				key: 'defaultStatuses',
				options: orderStatuses.filter( status => defaultOrderStatuses.includes( status.value ) ),
			},
			{
				key: 'customStatuses',
				label: __( 'Custom Statuses', 'woocommerce-admin' ),
				options: orderStatuses.filter( status => ! defaultOrderStatuses.includes( status.value ) ),
			},
		],
		helpText: interpolateComponents( {
			mixedString: __(
				'Orders with these statuses are excluded from the totals in your reports. ' +
					'The {{strong}}Refunded{{/strong}} status can not be excluded.  {{moreLink}}Learn more{{/moreLink}}',
				'woocommerce-admin'
			),
			components: {
				strong: <strong />,
				moreLink: <Link href="#" type="external" />, // @todo This needs to be replaced with a real link.
			},
		} ),
		initialValue: wcSettings.wcAdminSettings.woocommerce_excluded_report_order_statuses || [],
		defaultValue: [ 'pending', 'cancelled', 'failed' ],
	},
	{
		name: 'woocommerce_actionable_order_statuses',
		label: __( 'Actionable Statuses:', 'woocommerce-admin' ),
		inputType: 'checkboxGroup',
		options: [
			{
				key: 'defaultStatuses',
				options: orderStatuses.filter( status => defaultOrderStatuses.includes( status.value ) ),
			},
			{
				key: 'customStatuses',
				label: __( 'Custom Statuses', 'woocommerce-admin' ),
				options: orderStatuses.filter( status => ! defaultOrderStatuses.includes( status.value ) ),
			},
		],
		helpText: __(
			'Orders with these statuses require action on behalf of the store admin.' +
				'These orders will show up in the Orders tab under the activity panel.',
			'woocommerce-admin'
		),
		initialValue: wcSettings.wcAdminSettings.woocommerce_actionable_order_statuses || [],
		defaultValue: DEFAULT_ACTIONABLE_STATUSES,
	},
] );
