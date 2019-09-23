/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import interpolateComponents from 'interpolate-components';
import { getSetting, ORDER_STATUSES } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { DEFAULT_ACTIONABLE_STATUSES } from 'wc-api/constants';
import DefaultDate from './default-date';

const SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';
const DEFAULT_DATE_RANGE = 'period=month&compare=previous_year';

const defaultOrderStatuses = [
	'completed',
	'processing',
	'refunded',
	'cancelled',
	'failed',
	'pending',
	'on-hold',
];

const {
	woocommerce_actionable_order_statuses,
	woocommerce_excluded_report_order_statuses,
	woocommerce_default_date_range,
} = getSetting( 'wcAdminSettings', {
	woocommerce_actionable_order_statuses: [],
	woocommerce_excluded_report_order_statuses: [],
	woocommerce_default_date_range: DEFAULT_DATE_RANGE,
} );

const actionableOrderStatuses = Array.isArray( woocommerce_actionable_order_statuses )
	? woocommerce_actionable_order_statuses
	: [];

const excludedOrderStatuses = Array.isArray( woocommerce_excluded_report_order_statuses )
	? woocommerce_excluded_report_order_statuses
	: [];

const orderStatuses = Object.keys( ORDER_STATUSES )
	.filter( status => status !== 'refunded' )
	.map( key => {
		return {
			value: key,
			label: ORDER_STATUSES[ key ],
			description: sprintf(
				__( 'Exclude the %s status from reports', 'woocommerce-admin' ),
				ORDER_STATUSES[ key ]
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
					'The {{strong}}Refunded{{/strong}} status can not be excluded.',
				'woocommerce-admin'
			),
			components: {
				strong: <strong />,
			},
		} ),
		initialValue: [ ...excludedOrderStatuses ],
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
		initialValue: [ ...actionableOrderStatuses ],
		defaultValue: DEFAULT_ACTIONABLE_STATUSES,
	},
	{
		name: 'woocommerce_default_date_range',
		label: __( 'Default Date Range:', 'woocommerce-admin' ),
		inputType: 'component',
		component: DefaultDate,
		helpText: __(
			'Select a default date range. When no range is selected, reports will be viewed by ' +
				'the default date range.',
			'woocommerce-admin'
		),
		initialValue: woocommerce_default_date_range,
		defaultValue: DEFAULT_DATE_RANGE,
	},
] );
