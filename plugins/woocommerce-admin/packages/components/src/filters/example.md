```jsx
import { ReportFilters } from '@woocommerce/components';
const { orderStatuses } = wcSettings;

const path = '';
const query = {};
const filters = [
	{
		label: 'Show',
		staticParams: [ 'chart' ],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: 'All Orders', value: 'all' },
			{ label: 'Advanced Filters', value: 'advanced' },
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
				options: Object.keys( orderStatuses ).map( key => ( {
					value: key,
					label: orderStatuses[ key ],
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
				filter:'Select products',
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
				add: 'Customer Type',
				remove: 'Remove customer filter',
				rule: 'Select a customer filter match',
				title: 'Customer is {{filter /}}',
				filter: 'Select a customer type',
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'new', label: 'New', },
					{ value: 'returning', label: 'Returning', },
				],
				defaultOption: 'new',
			},
		},
	},
};

const compareFilter = {
	type: 'products',
	param: 'product',
	getLabels: function() {
		return Promise.resolve( [] );
	},
	labels: {
		helpText: 'Select at least two products to compare',
		placeholder: 'Search for products to compare',
		title: 'Compare Products',
		update: 'Compare',
	},
};

const MyReportFilters = () => (
	<div>
		<H>Date picker only</H>
		<Section component={ false }>
			<ReportFilters path={ path } query={ query } />
		</Section>

		<H>Date picker & more filters</H>
		<Section component={ false }>
			<ReportFilters
				filters={ filters }
				path={ path }
				query={ query }
			/>
		</Section>

		<H>Advanced Filters</H>
		<Section component={ false }>
			<AdvancedFilters
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
```
