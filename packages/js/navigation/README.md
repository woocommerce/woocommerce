# Navigation

A collection of navigation-related functions for handling query parameter objects, serializing query parameters, updating query parameters, and triggering path changes.

## Installation

Install the module

```bash
pnpm install @woocommerce/navigation --save
```

## Usage

### getHistory

A single history object used to perform path changes. This needs to be passed into ReactRouter to use the other path functions from this library.

```jsx
import { getHistory } from '@woocommerce/navigation';

render() {
	return (
		<Router history={ getHistory() }>
			…
		</Router>
	);
}
```

### getPath() ⇒ <code>String</code>
Get the current path from history.

**Returns**: <code>String</code> - Current path.

### getTimeRelatedQuery(query) ⇒ <code>Object</code>
Gets time related parameters from a query.

**Returns**: <code>Object</code> - Object containing the time related queries.

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | Query containing the parameters. |

### getIdsFromQuery(queryString) ⇒ <code>Array</code>
Get an array of IDs from a comma-separated query parameter.

**Returns**: <code>Array</code> - List of IDs converted to an array of unique integers.

| Param | Type | Description |
| --- | --- | --- |
| queryString | <code>string</code> | string value extracted from URL. |

### getNewPath(query, path, currentQuery) ⇒ <code>String</code>
Return a URL with set query parameters.

**Returns**: <code>String</code> - Updated URL merging query params into existing params.

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | object of params to be updated. |
| path | <code>String</code> | Relative path (defaults to current path). |
| currentQuery | <code>Object</code> | object of current query params (defaults to current querystring). |

### getQuery() ⇒ <code>Object</code>
Get the current query string, parsed into an object, from history.

**Returns**: <code>Object</code> - Current query object, defaults to empty object.

### onQueryChange(param, path, query) ⇒ <code>function</code>
This function returns an event handler for the given `param`

**Returns**: <code>function</code> - A callback which will update `param` to the passed value when called.

| Param | Type | Description |
| --- | --- | --- |
| param | <code>string</code> | The parameter in the querystring which should be updated (ex `page`, `per_page`) |
| path | <code>string</code> | Relative path (defaults to current path). |
| query | <code>string</code> | object of current query params (defaults to current querystring). |

### updateQueryString(query, path, currentQuery)
Updates the query parameters of the current page.

| Param | Type | Description |
| --- | --- | --- |
| query | <code>Object</code> | object of params to be updated. |
| path | <code>String</code> | Relative path (defaults to current path). |
| currentQuery | <code>Object</code> | object of current query params (defaults to current querystring). |

### flattenFilters(filters) ⇒ <code>Array</code>
Collapse an array of filter values with subFilters into a 1-dimensional array.

**Returns**: <code>Array</code> - Flattened array of all filters.

| Param | Type | Description |
| --- | --- | --- |
| filters | <code>Array</code> | Set of filters with possible subfilters. |

### getActiveFiltersFromQuery(query, config) ⇒ <code>Array.&lt;activeFilters&gt;</code>
Given a query object, return an array of activeFilters, if any.

**Returns**: <code>Array.&lt;activeFilters&gt;</code> - - array of activeFilters

| Param | Type | Description |
| --- | --- | --- |
| query | <code>object</code> | query oject |
| config | <code>object</code> | config object |

### getDefaultOptionValue(config, options) ⇒ <code>string</code> \| <code>undefined</code>
Get the default option's value from the configuration object for a given filter. The first option is used as default if no <code>defaultOption</code> is provided.

**Returns**: <code>string</code> \| <code>undefined</code> - - the value of the default option.

| Param | Type | Description |
| --- | --- | --- |
| config | <code>object</code> | a filter config object. |
| options | <code>array</code> | select options. |

### getQueryFromActiveFilters(activeFilters, query, config) ⇒ <code>object</code>
Given activeFilters, create a new query object to update the url. Use previousFilters to
Remove unused params.

**Returns**: <code>object</code> - - query object representing the new parameters

| Param | Type | Description |
| --- | --- | --- |
| activeFilters | <code>Array.&lt;activeFilters&gt;</code> | activeFilters shown in the UI |
| query | <code>object</code> | the current url query object |
| config | <code>object</code> | config object |

### getUrlKey(key, rule) ⇒ <code>string</code>
Get the url query key from the filter key and rule.

**Returns**: <code>string</code> - - url query key.

| Param | Type | Description |
| --- | --- | --- |
| key | <code>string</code> | filter key. |
| rule | <code>string</code> | filter rule. |

### activeFilter : <code>Object</code>
Describe activeFilter object.

**Properties**

| Name | Type | Description |
| --- | --- | --- |
| key | <code>string</code> | filter key. |
| [rule] | <code>string</code> | a modifying rule for a filter, eg 'includes' or 'is_not'. |
| value | <code>string</code> | filter value(s). |
