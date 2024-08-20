/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Story } from '@storybook/react';

/**
 * Internal dependencies
 */
import AnalyticsSummary from '../';

const Template: Story = ( args ) => <AnalyticsSummary { ...args } />;

const charts = [
	{
		key: 'items_sold',
		label: 'Items sold',
		order: 'desc',
		orderby: 'items_sold',
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: 'Net sales',
		order: 'desc',
		orderby: 'net_revenue',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: 'Orders',
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
];
const filters = [
	{
		label: 'Show',
		staticParams: [ 'chartType', 'paged', 'per_page' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{
				label: 'All categories',
				value: 'all',
			},
			{
				label: 'Single category',
				value: 'select_category',
				chartMode: 'item-comparison',
				subFilters: [
					{
						component: 'Search',
						value: 'single_category',
						chartMode: 'item-comparison',
						path: [ 'select_category' ],
						settings: {
							type: 'categories',
							param: 'categories',
							getLabels: () => {},
							labels: {
								placeholder: 'Type to search for a category',
								button: 'Single Category',
							},
						},
					},
				],
			},
			{
				label: 'Comparison',
				value: 'compare-categories',
				chartMode: 'item-comparison',
				settings: {
					type: 'categories',
					param: 'categories',
					getLabels: () => {},
					labels: {
						helpText:
							'Check at least two categories below to compare',
						placeholder: 'Search for categories to compare',
						title: 'Compare Categories',
						update: 'Compare',
					},
					onClick: () => {},
				},
			},
		],
	},
];

const advancedFilters = {
	filters: {},
	title: 'Categories match <select/> filters',
};

{
	/* <AnalyticsSummary
			limitProperties,
			summaryData,
			report,
			defaultDateRange,
/> */
}
export const Basic = Template.bind( {} );

export default {
	title: 'WooCommerce Admin/components/analytics/AnalyticsSummary',
	component: AnalyticsSummary,
	args: {
		charts,
		endpoint: 'coupons',
		limitProperties: [],
		query: '',
		selectedChart: '',
		summaryData: {},
		isRequesting: false,
		filters: {},
		advancedFilters,
		recordEvent: ( ...args ) => {
			// eslint-disable-next-line no-console
			console.log( `recordEvent called with `, ...args );
		},
	},
	argTypes: {
		isRequesting: {
			control: {
				type: 'boolean',
			},
		},
	},
};
