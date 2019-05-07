/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
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
		name: 'woocommerce_rebuild_reports_data',
		label: __( 'Rebuild reports data:', 'woocommerce-admin' ),
		inputType: 'button',
		inputText: __( 'Rebuild reports', 'woocommerce-admin' ),
		helpText: __(
			'This tool will rebuild all of the information used by the reports. ' +
				'Data will be processed in the background and may take some time depending on the size of your store.',
			'woocommerce-admin'
		),
		callback: ( resolve, reject, addNotice ) => {
			const errorMessage = __(
				'There was a problem rebuilding your report data.',
				'woocommerce-admin'
			);

			apiFetch( { path: '/wc/v4/reports/import', method: 'PUT' } )
				.then( response => {
					if ( 'success' === response.status ) {
						addNotice( { status: 'success', message: response.message } );
						// @todo This should be changed to detect when the lookup table population is complete.
						setTimeout( () => resolve(), 300000 );
					} else {
						addNotice( { status: 'error', message: errorMessage } );
						reject();
					}
				} )
				.catch( error => {
					if ( error && error.message ) {
						addNotice( { status: 'error', message: error.message } );
					}
					reject();
				} );
		},
	},
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
