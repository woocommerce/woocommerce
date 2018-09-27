```jsx
import { ReportFilters } from '@woocommerce/components';

const path = '';
const query = {};
const filters = [
	{ label: 'All Products', value: 'all' },
	{ label: 'Top Products by Items Sold', value: 'top_items' },
	{ label: 'Top Products by Gross Sales', value: 'top_sales' },
];

const advancedFilters = {
	status: {
		labels: {
			add: 'Order Status',
			remove: 'Remove order status filter',
			rule: 'Select an order status filter match',
			title: 'Order Status',
		},
		rules: [
			{ value: 'is', label: 'Is' },
			{ value: 'is_not', label: 'Is Not' },
		],
		input: {
			component: 'SelectControl',
			options: [
				{ value: 'pending', label: 'Pending' },
				{ value: 'processing', label: 'Processing' },
				{ value: 'on-hold', label: 'On Hold' },
				{ value: 'completed', label: 'Completed' },
				{ value: 'refunded', label: 'Refunded' },
				{ value: 'cancelled', label: 'Cancelled' },
				{ value: 'failed', label: 'Failed' },
			],
		},
	},
	product_id: {
		labels: {
			add: 'Products',
			placeholder: 'Search products',
			remove: 'Remove products filter',
			rule: 'Select a product filter match',
			title: 'Product',
		},
		rules: [
			{ value: 'includes', label: 'Includes' },
			{ value: 'excludes', label: 'Excludes' },
		],
		input: {
			component: 'Search',
			type: 'products',
			getLabels: function() {
				return Promise.resolve( [] );
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
