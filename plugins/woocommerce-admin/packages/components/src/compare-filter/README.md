CompareFilter
===

Displays a card + search used to filter results as a comparison between objects.

## Usage

```jsx
const path = ''; // from React Router
const getLabels = () => Promise.resolve( [] );
const labels = {
	helpText: 'Select at least two products to compare',
	placeholder: 'Search for products to compare',
	title: 'Compare Products',
	update: 'Compare',
};

<CompareFilter
	type="products"
	param="product"
	path={ path }
	getLabels={ getLabels }
	labels={ labels }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`getLabels` | Function | `null` | (required) Function used to fetch object labels via an API request, returns a Promise
`labels` | Object | `{}` | Object of localized labels
`param` | String | `null` | (required) The parameter to use in the querystring
`path` | String | `null` | (required) The `path` parameter supplied by React-Router
`query` | Object | `{}` | The query string represented in object form
`type` | String | `null` | (required) Which type of autocompleter should be used in the Search
