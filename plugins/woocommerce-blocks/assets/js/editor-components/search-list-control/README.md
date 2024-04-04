# SearchListControl <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Usage](#usage)
    -   [Props](#props)
    -   [`list` item structure:](#list-item-structure)
    -   [`messages` object structure:](#messages-object-structure)
-   [Usage](#usage)
    -   [Props](#props)

Component to display a searchable, selectable list of items.

## Usage

```jsx
<SearchListControl
	list={ list }
	isLoading={ loading }
	selected={ selected }
	onChange={ ( items ) => setState( { selected: items } ) }
/>
```

### Props

| Name             | Type     | Default | Description                                                                                                                                                                   |
| ---------------- | -------- | ------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `className`      | String   | `null`  | Additional CSS classes                                                                                                                                                        |
| `isHierarchical` | Boolean  | `null`  | Whether the list of items is hierarchical or not. If true, each list item is expected to have a parent property                                                               |
| `isLoading`      | Boolean  | `null`  | Whether the list of items is still loading                                                                                                                                    |
| `isSingle`       | Boolean  | `null`  | Restrict selections to one item                                                                                                                                               |
| `list`           | Array    | `null`  | A complete list of item objects, each with id, name properties. This is displayed as a clickable/keyboard-able list, and possibly filtered by the search term (searches name) |
| `messages`       | Object   | `null`  | Messages displayed or read to the user. Configure these to reflect your object type. See `defaultMessages` above for examples                                                 |
| `onChange`       | Function | `null`  | (required) Callback fired when selected items change, whether added, cleared, or removed. Passed an array of item objects (as passed in via props.list)                       |
| `onSearch`       | Function | `null`  | Callback fired when the search field is used                                                                                                                                  |
| `renderItem`     | Function | `null`  | Callback to render each item in the selection list, allows any custom object-type rendering                                                                                   |
| `selected`       | Array    | `null`  | (required) The list of currently selected items                                                                                                                               |
| `search`         | String   | `null`  |
| `setState`       | Function | `null`  |
| `debouncedSpeak` | Function | `null`  |
| `instanceId`     | Number   | `null`  |

### `list` item structure

-   `id`: Number
-   `name`: String

### `messages` object structure

-   `clear`: String - A more detailed label for the "Clear all" button, read to screen reader users.
-   `list`: String - Label for the list of selectable items, only read to screen reader users.
-   `noItems`: String - Message to display when the list is empty (implies nothing loaded from the server
    or parent component).
-   `noResults`: String - Message to display when no matching results are found. %s is the search term.
-   `search`: String - Label for the search input
-   `selected`: Function - Label for the selected items. This is actually a function, so that we can pass
    through the count of currently selected items.
-   `updated`: String - Label indicating that search results have changed, read to screen reader users.

# SearchListItem

## Usage

Used implicitly by `SearchListControl` when the `renderItem` prop is omitted.

### Props

| Name         | Type      | Default | Description                                                                      |
| ------------ | --------- | ------- | -------------------------------------------------------------------------------- |
| `className`  | String    | `null`  | Additional CSS classes                                                           |
| `countLabel` | ReactNode | `null`  | Label to display in the count bubble. Takes preference over `item.count`.        |
| `depth`      | Number    | `0`     | Depth, non-zero if the list is hierarchical                                      |
| `item`       | Object    | `null`  | Current item to display                                                          |
| `isSelected` | Boolean   | `null`  | Whether this item is selected                                                    |
| `isSingle`   | Boolean   | `null`  | Whether this should only display a single item (controls radio vs checkbox icon) |
| `onSelect`   | Function  | `null`  | Callback for selecting the item                                                  |
| `search`     | String    | `''`    | Search string, used to highlight the substring in the item name                  |

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/README.md)

<!-- /FEEDBACK -->
