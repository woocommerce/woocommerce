/** @format */
/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getRequestByIdString } from '../../../lib/async-requests';
import { NAMESPACE } from '../../../store/constants';

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Registered Customers', 'wc-admin' ), value: 'all' },
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
				// Use products autocompleter for now, see https://github.com/woocommerce/wc-admin/issues/1029 for progress
				component: 'Search',
				type: 'products',
				getLabels: getRequestByIdString( NAMESPACE + 'products', product => ( {
					id: product.id,
					label: product.name,
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
	},
};
/*eslint-enable max-len*/
