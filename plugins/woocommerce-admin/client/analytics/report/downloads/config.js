/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	getCustomerLabels,
	getProductLabels,
} from '../../../lib/async-requests';

const DOWLOADS_REPORT_CHARTS_FILTER =
	'woocommerce_admin_downloads_report_charts';
const DOWLOADS_REPORT_FILTERS_FILTER =
	'woocommerce_admin_downloads_report_filters';
const DOWLOADS_REPORT_ADVANCED_FILTERS_FILTER =
	'woocommerce_admin_downloads_report_advanced_filters';

export const charts = applyFilters( DOWLOADS_REPORT_CHARTS_FILTER, [
	{
		key: 'download_count',
		label: __( 'Downloads', 'woocommerce-admin' ),
		type: 'number',
	},
] );

export const filters = applyFilters( DOWLOADS_REPORT_FILTERS_FILTER, [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All downloads', 'woocommerce-admin' ), value: 'all' },
			{
				label: __( 'Advanced filters', 'woocommerce-admin' ),
				value: 'advanced',
			},
		],
	},
] );

/*eslint-disable max-len*/
export const advancedFilters = applyFilters(
	DOWLOADS_REPORT_ADVANCED_FILTERS_FILTER,
	{
		title: _x(
			'Downloads match {{select /}} filters',
			'A sentence describing filters for Downloads. See screen shot for context: https://cloudup.com/ccxhyH2mEDg',
			'woocommerce-admin'
		),
		filters: {
			product: {
				labels: {
					add: __( 'Product', 'woocommerce-admin' ),
					placeholder: __( 'Search', 'woocommerce-admin' ),
					remove: __( 'Remove product filter', 'woocommerce-admin' ),
					rule: __(
						'Select a product filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
					title: __(
						'{{title}}Product{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select product', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to products including a given product(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Includes',
							'products',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to products excluding a products(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Excludes',
							'products',
							'woocommerce-admin'
						),
					},
				],
				input: {
					component: 'Search',
					type: 'products',
					getLabels: getProductLabels,
				},
			},
			customer: {
				labels: {
					add: __( 'Username', 'woocommerce-admin' ),
					placeholder: __(
						'Search customer username',
						'woocommerce-admin'
					),
					remove: __(
						'Remove customer username filter',
						'woocommerce-admin'
					),
					rule: __(
						'Select a customer username filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a customer username filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
					title: __(
						'{{title}}Username{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __(
						'Select customer username',
						'woocommerce-admin'
					),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to customer usernames including a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Includes',
							'customer usernames',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to customer usernames excluding a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Excludes',
							'customer usernames',
							'woocommerce-admin'
						),
					},
				],
				input: {
					component: 'Search',
					type: 'usernames',
					getLabels: getCustomerLabels,
				},
			},
			order: {
				labels: {
					add: __( 'Order #', 'woocommerce-admin' ),
					placeholder: __(
						'Search order number',
						'woocommerce-admin'
					),
					remove: __(
						'Remove order number filter',
						'woocommerce-admin'
					),
					rule: __(
						'Select a order number filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
					title: __(
						'{{title}}Order #{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select order number', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to order numbers including a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Includes',
							'order numbers',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to order numbers excluding a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Excludes',
							'order numbers',
							'woocommerce-admin'
						),
					},
				],
				input: {
					component: 'Search',
					type: 'orders',
					getLabels: async ( value ) => {
						const orderIds = value.split( ',' );
						return await orderIds.map( ( orderId ) => ( {
							id: orderId,
							label: '#' + orderId,
						} ) );
					},
				},
			},
			ip_address: {
				labels: {
					add: __( 'IP Address', 'woocommerce-admin' ),
					placeholder: __( 'Search IP address', 'woocommerce-admin' ),
					remove: __(
						'Remove IP address filter',
						'woocommerce-admin'
					),
					rule: __(
						'Select an IP address filter match',
						'woocommerce-admin'
					),
					/* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
					title: __(
						'{{title}}IP Address{{/title}} {{rule /}} {{filter /}}',
						'woocommerce-admin'
					),
					filter: __( 'Select IP address', 'woocommerce-admin' ),
				},
				rules: [
					{
						value: 'includes',
						/* translators: Sentence fragment, logical, "Includes" refers to IP addresses including a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Includes',
							'IP addresses',
							'woocommerce-admin'
						),
					},
					{
						value: 'excludes',
						/* translators: Sentence fragment, logical, "Excludes" refers to IP addresses excluding a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
						label: _x(
							'Excludes',
							'IP addresses',
							'woocommerce-admin'
						),
					},
				],
				input: {
					component: 'Search',
					type: 'downloadIps',
					getLabels: async ( value ) => {
						const ips = value.split( ',' );
						return await ips.map( ( ip ) => {
							return {
								id: ip,
								label: ip,
							};
						} );
					},
				},
			},
		},
	}
);
/*eslint-enable max-len*/
