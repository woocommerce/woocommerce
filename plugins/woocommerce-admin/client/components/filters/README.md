ReportFilters
=============

Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters` or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.

## Usage

```jsx
// For just DatePicker…
<ReportFilters path={ path } query={ query } />

// To add FilterPicker too, pass through filter config:
<ReportFilters
	filters={ filters }
	path={ path }
	query={ query } />
```

- `advancedConfig`: Config option passed through to `AdvancedFilters`
- `filters`: Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
- `path` (required): The `path` parameter supplied by React-Router
- `query`: The query string represented in object form

`path` & `query` are passed through to the individual filter components.
