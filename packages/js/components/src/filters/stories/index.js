/**
 * External dependencies
 */
import {
	AdvancedFilters,
	CompareFilter,
	H,
	ReportFilters,
	Section,
} from '@woocommerce/components';
import {
	getDateParamsFromQuery,
	getCurrentDates,
	isoDateFormat,
} from '@woocommerce/date';
import { partialRight } from 'lodash';

const ORDER_STATUSES = {
	cancelled: 'Cancelled',
	completed: 'Completed',
	failed: 'Failed',
	'on-hold': 'On hold',
	pending: 'Pending payment',
	processing: 'Processing',
	refunded: 'Refunded',
};

// Fetch store default date range and compose with date utility functions.
const defaultDateRange = 'period=month&compare=previous_year';
const storeGetDateParamsFromQuery = partialRight(
	getDateParamsFromQuery,
	defaultDateRange
);
const storeGetCurrentDates = partialRight( getCurrentDates, defaultDateRange );

// Package date utilities for filter picker component.
const storeDate = {
	getDateParamsFromQuery: storeGetDateParamsFromQuery,
	getCurrentDates: storeGetCurrentDates,
	isoDateFormat,
};

const siteLocale = 'en_US';

const path = '';
const query = {};
const filters = [
	{
		label: 'Show',
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: 'All orders', value: 'all' },
			{ label: 'Advanced filters', value: 'advanced' },
		],
	},
];

const advancedFilters = {
	title: 'Orders Match {{select /}} Filters',
	filters: {
		status: {
			labels: {
				add: 'Order Status',
				remove: 'Remove order status filter',
				rule: 'Select an order status filter match',
				title: 'Order Status {{rule /}} {{filter /}}',
				filter: 'Select an order status',
			},
			rules: [
				{
					value: 'is',
					label: 'Is',
				},
				{
					value: 'is_not',
					label: 'Is Not',
				},
			],
			input: {
				component: 'SelectControl',
				options: Object.keys( ORDER_STATUSES ).map( ( key ) => ( {
					value: key,
					label: ORDER_STATUSES[ key ],
				} ) ),
			},
		},
		product: {
			labels: {
				add: 'Products',
				placeholder: 'Search products',
				remove: 'Remove products filter',
				rule: 'Select a product filter match',
				title: 'Product {{rule /}} {{filter /}}',
				filter: 'Select products',
			},
			rules: [
				{
					value: 'includes',
					label: 'Includes',
				},
				{
					value: 'excludes',
					label: 'Excludes',
				},
			],
			input: {
				component: 'Search',
				type: 'products',
				getLabels: () => Promise.resolve( [] ),
			},
		},
		customer: {
			labels: {
				add: 'Customer type',
				remove: 'Remove customer filter',
				rule: 'Select a customer filter match',
				title: 'Customer is {{filter /}}',
				filter: 'Select a customer type',
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'new', label: 'New' },
					{ value: 'returning', label: 'Returning' },
				],
				defaultOption: 'new',
			},
		},
		quantity: {
			labels: {
				add: 'Item Quantity',
				remove: 'Remove item quantity filter',
				rule: 'Select an item quantity filter match',
				title: 'Item Quantity is {{rule /}} {{filter /}}',
			},
			rules: [
				{
					value: 'lessthan',
					label: 'Less Than',
				},
				{
					value: 'morethan',
					label: 'More Than',
				},
				{
					value: 'between',
					label: 'Between',
				},
			],
			input: {
				component: 'Number',
			},
		},
		subtotal: {
			labels: {
				add: 'Subtotal',
				remove: 'Remove subtotal filter',
				rule: 'Select a subtotal filter match',
				title: 'Subtotal is {{rule /}} {{filter /}}',
			},
			rules: [
				{
					value: 'lessthan',
					label: 'Less Than',
				},
				{
					value: 'morethan',
					label: 'More Than',
				},
				{
					value: 'between',
					label: 'Between',
				},
			],
			input: {
				component: 'Number',
				type: 'currency',
			},
		},
	},
};

const compareFilter = {
	type: 'products',
	param: 'product',
	getLabels() {
		return Promise.resolve( [] );
	},
	labels: {
		helpText: 'Select at least two products to compare',
		placeholder: 'Search for products to compare',
		title: 'Compare Products',
		update: 'Compare',
	},
};

export const Examples = () => (
	<div>
		<H>Date picker only</H>
		<Section component={ false }>
			<ReportFilters
				path={ path }
				query={ query }
				storeDate={ storeDate }
			/>
		</Section>

		<H>Date picker & more filters</H>
		<Section component={ false }>
			<ReportFilters
				filters={ filters }
				path={ path }
				query={ query }
				storeDate={ storeDate }
			/>
		</Section>

		<H>Advanced filters</H>
		<Section component={ false }>
			<AdvancedFilters
				siteLocale={ siteLocale }
				path={ path }
				query={ query }
				filterTitle="Orders"
				config={ advancedFilters }
			/>
		</Section>

		<H>Compare Filter</H>
		<Section component={ false }>
			<CompareFilter path={ path } query={ query } { ...compareFilter } />
		</Section>
	</div>
);

export default {
	title: 'WooCommerce Admin/components/ReportFilters',
	component: ReportFilters,
};
