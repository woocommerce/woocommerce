ReportFilters
=============

Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters` or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.

## Usage

```jsx
// For just DatePicker…
<ReportFilters />

// To add FilterPicker too, pass through filter config:
<ReportFilters
	filters={ filters }
	filterPaths={ filterPaths } />
```

- `advancedConfig`: Config option passed through to `AdvancedFilters`
- `filters`: Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
- `filterPaths`: Config option passed through to `FilterPicker`
