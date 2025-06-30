/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import DefaultDate from './default-date';
import { getAdminSetting, ORDER_STATUSES } from '~/utils/admin-settings';

const SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';
export const DEFAULT_ACTIONABLE_STATUSES = [ 'processing', 'on-hold' ];
export const DEFAULT_ORDER_STATUSES = [
	'completed',
	'processing',
	'refunded',
	'cancelled',
	'failed',
	'pending',
	'on-hold',
];
export const DEFAULT_DATE_RANGE = 'period=month&compare=previous_year';

const filteredOrderStatuses = Object.keys( ORDER_STATUSES )
	.filter( ( status ) => status !== 'refunded' )
	.map( ( key ) => {
		return {
			value: key,
			label: ORDER_STATUSES[ key ],
			description: sprintf(
				/* translators: %s: non-refunded order statuses to exclude */
				__( 'Exclude the %s status from reports', 'woocommerce' ),
				ORDER_STATUSES[ key ]
			),
		};
	} );

const unregisteredOrderStatuses = getAdminSetting(
	'unregisteredOrderStatuses',
	{}
);

const orderStatusOptions = [
	{
		key: 'defaultStatuses',
		options: filteredOrderStatuses.filter( ( status ) =>
			DEFAULT_ORDER_STATUSES.includes( status.value )
		),
	},
	{
		key: 'customStatuses',
		label: __( 'Custom Statuses', 'woocommerce' ),
		options: filteredOrderStatuses.filter(
			( status ) => ! DEFAULT_ORDER_STATUSES.includes( status.value )
		),
	},
	{
		key: 'unregisteredStatuses',
		label: __( 'Unregistered Statuses', 'woocommerce' ),
		options: Object.keys( unregisteredOrderStatuses ).map( ( key ) => {
			return {
				value: key,
				label: key,
				description: sprintf(
					/* translators: %s: unregistered order statuses to exclude */
					__( 'Exclude the %s status from reports', 'woocommerce' ),
					key
				),
			};
		} ),
	},
];

/**
 * Filter Analytics Report settings. Add a UI element to the Analytics Settings page.
 *
 * @filter woocommerce_admin_analytics_settings
 * @param {Object} reportSettings Report settings.
 */
export const config = applyFilters( SETTINGS_FILTER, {
	woocommerce_excluded_report_order_statuses: {
		label: __( 'Excluded statuses:', 'woocommerce' ),
		inputType: 'checkboxGroup',
		options: orderStatusOptions,
		helpText: interpolateComponents( {
			mixedString: __(
				'Orders with these statuses are excluded from the totals in your reports. ' +
					'The {{strong}}Refunded{{/strong}} status can not be excluded.',
				'woocommerce'
			),
			components: {
				strong: <strong />,
			},
		} ),
		defaultValue: [ 'pending', 'cancelled', 'failed' ],
	},
	woocommerce_actionable_order_statuses: {
		label: __( 'Actionable statuses:', 'woocommerce' ),
		inputType: 'checkboxGroup',
		options: orderStatusOptions,
		helpText: __(
			'Orders with these statuses require action on behalf of the store admin. ' +
				'These orders will show up in the Home Screen - Orders task.',
			'woocommerce'
		),
		defaultValue: DEFAULT_ACTIONABLE_STATUSES,
	},
	woocommerce_default_date_range: {
		name: 'woocommerce_default_date_range',
		label: __( 'Default date range:', 'woocommerce' ),
		inputType: 'component',
		component: DefaultDate,
		helpText: __(
			'Select a default date range. When no range is selected, reports will be viewed by ' +
				'the default date range.',
			'woocommerce'
		),
		defaultValue: DEFAULT_DATE_RANGE,
	},
	woocommerce_date_type: {
		name: 'woocommerce_date_type',
		label: __( 'Date type:', 'woocommerce' ),
		inputType: 'select',
		options: [
			{
				label: __( 'Select a date type', 'woocommerce' ),
				value: '',
				disabled: true,
			},
			{
				label: __( 'Date created', 'woocommerce' ),
				value: 'date_created',
				key: 'date_created',
			},
			{
				label: __( 'Date paid', 'woocommerce' ),
				value: 'date_paid',
				key: 'date_paid',
			},
			{
				label: __( 'Date completed', 'woocommerce' ),
				value: 'date_completed',
				key: 'date_completed',
			},
		],
		helpText: __(
			'Database date field considered for Revenue and Orders reports',
			'woocommerce'
		),
	},
} );
