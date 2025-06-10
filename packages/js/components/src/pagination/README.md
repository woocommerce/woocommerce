# Pagination

Use `Pagination` to allow navigation between pages that represent a collection of items.
The component allows for selecting a new page and items per page options.

You can also make use of the `usePagination` hook and the custom components:

-   `PaginationPageArrowsWithPicker`
-   `PaginationPageArrows`
-   `PaginationPagePicker`
-   `PaginationPageSizePicker`

## Usage

```jsx
<Pagination
	page={ 1 }
	perPage={ 10 }
	total={ 500 }
	onPageChange={ ( newPage ) => setState( { page: newPage } ) }
	onPerPageChange={ ( newPerPage ) => setState( { perPage: newPerPage } ) }
/>
```

## Custom Example

```jsx
const paginationProps = usePagination( {
	totalCount: 200,
	defaultPerPage: 25,
} );

return (
	<div>
		<div>
			Viewing { paginationProps.start }-{ paginationProps.end } of 200
			items
		</div>
		<PaginationPageArrowsWithPicker { ...paginationProps } />
		<PaginationPageSizePicker
			{ ...paginationProps }
			total={ 200 }
			perPageOptions={ [ 5, 10, 25 ] }
			label=""
		/>
	</div>
);
```

### Props

| Name                  | Type     | Default | Description                                                        |
| --------------------- | -------- | ------- | ------------------------------------------------------------------ |
| `page`                | Number   | `null`  | (required) The current page of the collection                      |
| `onPageChange`        | Function | `noop`  | A function to execute when the page is changed                     |
| `perPage`             | Number   | `null`  | (required) The amount of results that are being displayed per page |
| `onPerPageChange`     | Function | `noop`  | A function to execute when the per page option is changed          |
| `total`               | Number   | `null`  | (required) The total number of results                             |
| `className`           | String   | `null`  | Additional classNames                                              |
| `showPagePicker`      | Boolean  | `true`  | Whether the page picker should be shown.                           |
| `showPerPagePicker`   | Boolean  | `true`  | Whether the per page picker should shown.                          |
| `showPageArrowsLabel` | Boolean  | `true`  | Whether the page arrows label should be shown.                     |
