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

### getPath() ⇒ `String`

Get the current path from history.

**Returns**: `String` - Current path.

### getTimeRelatedQuery(query) ⇒ `Object`

Gets time related parameters from a query.

**Returns**: `Object` - Object containing the time related queries.

| Param | Type | Description |
| --- | --- | --- |
| query | `Object` | Query containing the parameters. |

### getIdsFromQuery(queryString) ⇒ `Array`

Get an array of IDs from a comma-separated query parameter.

**Returns**: `Array` - List of IDs converted to an array of unique integers.

| Param | Type | Description |
| --- | --- | --- |
| queryString | `string` | string value extracted from URL. |

### getNewPath(query, path, currentQuery, page) ⇒ `String`

Return a URL with set query parameters.

**Returns**: `String` - Updated URL merging query params into existing params.

| Param | Type | Description |
| --- | --- | --- |
| query | `Object` | object of params to be updated. |
| path | `String` | Admin page path, e.g. `/analytics/orders` (defaults to the current path). Used as-is for the `path` query arg; `./` and `../` segments are not resolved against the current path. |
| currentQuery | `Object` | object of current query params (defaults to current querystring). |
| page | `String` | Page key (defaults to "wc-admin"). |

### getQuery() ⇒ `Object`

Get the current query string, parsed into an object, from history.

**Returns**: `Object` - Current query object, defaults to empty object.

### onQueryChange(param, path, query) ⇒ `function`

This function returns an event handler for the given `param`

**Returns**: `function` - A callback which will update `param` to the passed value when called.

| Param | Type | Description |
| --- | --- | --- |
| param | `string` | The parameter in the querystring which should be updated (ex `page`, `per_page`) |
| path | `string` | Admin page path, e.g. `/analytics/orders` (defaults to the current path). Used as-is for the `path` query arg; `./` and `../` segments are not resolved against the current path. |
| query | `Object` | object of current query params (defaults to current querystring). |

### updateQueryString(query, path, currentQuery, page)

Updates the query parameters of the current page.

| Param | Type | Description |
| --- | --- | --- |
| query | `Object` | object of params to be updated. |
| path | `String` | Admin page path, e.g. `/analytics/orders` (defaults to the current path). Used as-is for the `path` query arg; `./` and `../` segments are not resolved against the current path. |
| currentQuery | `Object` | object of current query params (defaults to current querystring). |
| page | `String` | Page key (defaults to "wc-admin"). |

### flattenFilters(filters) ⇒ `Array`

Collapse an array of filter values with subFilters into a 1-dimensional array.

**Returns**: `Array` - Flattened array of all filters.

| Param | Type | Description |
| --- | --- | --- |
| filters | `Array` | Set of filters with possible subfilters. |

### getActiveFiltersFromQuery(query, config) ⇒ `Array.<activeFilters>`

Given a query object, return an array of activeFilters, if any.

**Returns**: `Array.<activeFilters>` - - array of activeFilters

| Param | Type | Description |
| --- | --- | --- |
| query | `object` | query object |
| config | `object` | config object |

### getDefaultOptionValue(config, options) ⇒ `string` \| `undefined`

Get the default option's value from the configuration object for a given filter. The first option is used as default if no `defaultOption` is provided.

**Returns**: `string` \| `undefined` - - the value of the default option.

| Param | Type | Description |
| --- | --- | --- |
| config | `object` | a filter config object. |
| options | `array` | select options. |

### getQueryFromActiveFilters(activeFilters, query, config) ⇒ `object`

Given activeFilters, create a new query object to update the url. Use previousFilters to
Remove unused params.

**Returns**: `object` - - query object representing the new parameters

| Param | Type | Description |
| --- | --- | --- |
| activeFilters | `Array.<activeFilters>` | activeFilters shown in the UI |
| query | `object` | the current url query object |
| config | `object` | config object |

### getUrlKey(key, rule) ⇒ `string`

Get the url query key from the filter key and rule.

**Returns**: `string` - - url query key.

| Param | Type | Description |
| --- | --- | --- |
| key | `string` | filter key. |
| rule | `string` | filter rule. |

### activeFilter : `Object`

Describe activeFilter object.

#### Properties

| Name | Type | Description |
| --- | --- | --- |
| key | `string` | filter key. |
| [rule] | `string` | a modifying rule for a filter, eg 'includes' or 'is_not'. |
| value | `string` | filter value(s). |
