```jsx
import { CompareFilter } from '@woocommerce/components';

const path = (new URL(document.location)).searchParams.get('path') || '/devdocs';
const query = {};
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

const MyCompareFilter = () => (
    <CompareFilter path={ path } query={ query } { ...compareFilter } />
);
```