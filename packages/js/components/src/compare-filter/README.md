# CompareFilter

Displays a card + search used to filter results as a comparison between objects.

## Usage

```jsx
const path = ''; // from React Router
const getLabels = () => Promise.resolve( [] );
const labels = {
	helpText: 'Select at least two products to compare',
	title: 'Compare Products',
	update: 'Compare',
};
const searchProps = {
	type: 'products',
	placeholder: 'Search for products to compare',
};

<CompareFilter
	param="product"
	path={ path }
	getLabels={ getLabels }
	labels={ labels }
	searchProps={ searchProps }
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
`searchProps` | Object | `{}` | Props forwarded to the `Search` component, except `selected` and `onChange`. `searchProps.type` is required. See [Search](../search/README.md) for the full list
`type` | String | `null` | Deprecated. Use `searchProps.type` instead
`autocompleter` | Object | `null` | Deprecated. Use `searchProps.autocompleter` instead

The `labels.placeholder` label is deprecated. Use `searchProps.placeholder` instead. When a deprecated prop is set, it takes precedence over the matching `searchProps` value.
