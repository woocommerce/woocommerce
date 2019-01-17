/** @format */
/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { NAMESPACE } from 'store/constants';

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Customers', 'wc-admin' ), value: 'all' },
			{ label: __( 'Advanced Filters', 'wc-admin' ), value: 'advanced' },
		],
	},
];

/*eslint-disable max-len*/
export const advancedFilters = {
	title: _x(
		'Customers Match {{select /}} Filters',
		'A sentence describing filters for Customers. See screen shot for context: https://cloudup.com/cCsm3GeXJbE',
		'wc-admin'
	),
	filters: {
		name: {
			labels: {
				add: __( 'Name', 'wc-admin' ),
				placeholder: __( 'Search', 'wc-admin' ),
				remove: __( 'Remove customer name filter', 'wc-admin' ),
				rule: __( 'Select a customer name filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
				title: __( 'Name {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select customer name', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to customer names including a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Includes', 'customer names', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to customer names excluding a given name(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Excludes', 'customer names', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'customers',
				getLabels: getRequestByIdString( NAMESPACE + 'customers', customer => ( {
					id: customer.id,
					label: [ customer.first_name, customer.last_name ].filter( Boolean ).join( ' ' ),
				} ) ),
			},
		},
		country: {
			labels: {
				add: __( 'Country', 'wc-admin' ),
				placeholder: __( 'Search', 'wc-admin' ),
				remove: __( 'Remove country filter', 'wc-admin' ),
				rule: __( 'Select a country filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
				title: __( 'Country {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select country', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to countries including a given country or countries. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Includes', 'countries', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to countries excluding a given country or countries. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Excludes', 'countries', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'countries',
				getLabels: async value => {
					const countries =
						( wcSettings.dataEndpoints && wcSettings.dataEndpoints.countries ) || [];

					const allLabels = countries.map( country => ( {
						id: country.code,
						label: decodeEntities( country.name ),
					} ) );

					const labels = value.split( ',' );
					return await allLabels.filter( label => {
						return labels.includes( label.id );
					} );
				},
			},
		},
		username: {
			labels: {
				add: __( 'Username', 'wc-admin' ),
				placeholder: __( 'Search customer username', 'wc-admin' ),
				remove: __( 'Remove customer username filter', 'wc-admin' ),
				rule: __( 'Select a customer username filter match', 'wc-admin' ),
				/* translators: A sentence describing a customer username filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
				title: __( 'Username {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select customer username', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to customer usernames including a given username(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Includes', 'customer usernames', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to customer usernames excluding a given username(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Excludes', 'customer usernames', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'usernames',
				getLabels: getRequestByIdString( NAMESPACE + 'customers', customer => ( {
					id: customer.id,
					label: customer.username,
				} ) ),
			},
		},
		email: {
			labels: {
				add: __( 'Email', 'wc-admin' ),
				placeholder: __( 'Search customer email', 'wc-admin' ),
				remove: __( 'Remove customer email filter', 'wc-admin' ),
				rule: __( 'Select a customer email filter match', 'wc-admin' ),
				/* translators: A sentence describing a customer email filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
				title: __( 'Email {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select customer email', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to customer emails including a given email(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Includes', 'customer emails', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to customer emails excluding a given email(s). Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Excludes', 'customer emails', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'emails',
				getLabels: getRequestByIdString( NAMESPACE + 'customers', customer => ( {
					id: customer.id,
					label: customer.email,
				} ) ),
			},
		},
		order_count: {
			labels: {
				add: __( 'No. of Orders', 'wc-admin' ),
				remove: __( 'Remove order  filter', 'wc-admin' ),
				rule: __( 'Select an order count filter match', 'wc-admin' ),
				title: __( 'No. of Orders {{rule /}} {{filter /}}', 'wc-admin' ),
			},
			rules: [
				{
					value: 'max',
					/* translators: Sentence fragment, logical, "Less Than" refers to number of orders a customer has placed, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Less Than', 'number of orders', 'wc-admin' ),
				},
				{
					value: 'min',
					/* translators: Sentence fragment, logical, "More Than" refers to number of orders a customer has placed, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'More Than', 'number of orders', 'wc-admin' ),
				},
				{
					value: 'between',
					/* translators: Sentence fragment, logical, "Between" refers to number of orders a customer has placed, between two given integers. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Between', 'number of orders', 'wc-admin' ),
				},
			],
			input: {
				component: 'Number',
			},
		},
		total_spend: {
			labels: {
				add: __( 'Total Spend', 'wc-admin' ),
				remove: __( 'Remove total spend filter', 'wc-admin' ),
				rule: __( 'Select a total spend filter match', 'wc-admin' ),
				title: __( 'Total Spend {{rule /}} {{filter /}}', 'wc-admin' ),
			},
			rules: [
				{
					value: 'max',
					/* translators: Sentence fragment, logical, "Less Than" refers to total spending by a customer, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Less Than', 'total spend by customer', 'wc-admin' ),
				},
				{
					value: 'min',
					/* translators: Sentence fragment, logical, "Less Than" refers to total spending by a customer, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'More Than', 'total spend by customer', 'wc-admin' ),
				},
				{
					value: 'between',
					/* translators: Sentence fragment, logical, "Between" refers to total spending by a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Between', 'total spend by customer', 'wc-admin' ),
				},
			],
			input: {
				component: 'Currency',
			},
		},
		avg_order_value: {
			labels: {
				add: __( 'AOV', 'wc-admin' ),
				remove: __( 'Remove average older value filter', 'wc-admin' ),
				rule: __( 'Select an average order value filter match', 'wc-admin' ),
				title: __( 'AOV {{rule /}} {{filter /}}', 'wc-admin' ),
			},
			rules: [
				{
					value: 'max',
					/* translators: Sentence fragment, logical, "Less Than" refers to average order value of a customer, more than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Less Than', 'average order value of customer', 'wc-admin' ),
				},
				{
					value: 'min',
					/* translators: Sentence fragment, logical, "Less Than" refers to average order value of a customer, less than a given amount. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */

					label: _x( 'More Than', 'average order value of customer', 'wc-admin' ),
				},
				{
					value: 'between',
					/* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Between', 'average order value of customer', 'wc-admin' ),
				},
			],
			input: {
				component: 'Currency',
			},
		},
		registered: {
			labels: {
				add: __( 'Registered', 'wc-admin' ),
				remove: __( 'Remove registered filter', 'wc-admin' ),
				rule: __( 'Select a registered filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
				title: __( 'Registered {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select registered date', 'wc-admin' ),
			},
			rules: [
				{
					value: 'before',
					/* translators: Sentence fragment, logical, "Before" refers to customers registered before a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Before', 'date', 'wc-admin' ),
				},
				{
					value: 'after',
					/* translators: Sentence fragment, logical, "after" refers to customers registered after a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'After', 'date', 'wc-admin' ),
				},
				{
					value: 'between',
					/* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Between', 'date', 'wc-admin' ),
				},
			],
			input: {
				component: 'Date',
			},
		},
		last_active: {
			labels: {
				add: __( 'Last active', 'wc-admin' ),
				remove: __( 'Remove last active filter', 'wc-admin' ),
				rule: __( 'Select a last active filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/cCsm3GeXJbE */
				title: __( 'Last active {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select registered date', 'wc-admin' ),
			},
			rules: [
				{
					value: 'before',
					/* translators: Sentence fragment, logical, "Before" refers to customers registered before a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Before', 'date', 'wc-admin' ),
				},
				{
					value: 'after',
					/* translators: Sentence fragment, logical, "after" refers to customers registered after a given date. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'After', 'date', 'wc-admin' ),
				},
				{
					value: 'between',
					/* translators: Sentence fragment, logical, "Between" refers to average order value of a customer, between two given amounts. Screenshot for context: https://cloudup.com/cCsm3GeXJbE */
					label: _x( 'Between', 'date', 'wc-admin' ),
				},
			],
			input: {
				component: 'Date',
			},
		},
	},
};
/*eslint-enable max-len*/
