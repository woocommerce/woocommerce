Pagination
============

Use `Pagination` to allow navigation between pages that represent a collection of items. The component allows for selecting a new page and items per page options.

## How to use:

```jsx
import { Pagination } from 'components/pagination';

render: function() {
  return (
    <Pagination
		page={ this.state.page }
		perPage={ this.state.perPage }
		total={ 5000 }
		onPageChange={ this.onPageChange }
		onPerPageChange={ this.onPerPageChange }
	/>
  );
}
```

## Props

* `page` (required): The current page of the collection.
* `onPageChange`: A function to execute when the page is changed.
* `perPage` (required): The amount of results that are being displayed per page.
* `onPerPageChange`: A function to execute when the per page option is changed.
* `total` (required): The total number of results.
* `className`: Additional classNames.
