`SearchListControl` (component)
===============================

Component to display a searchable, selectable list of items.

Props
-----

### `className`

- Type: String
- Default: null

Additional CSS classes.

### `isHierarchical`

- Type: Boolean
- Default: null

Whether the list of items is hierarchical or not. If true, each list item is expected to
have a parent property.

### `isLoading`

- Type: Boolean
- Default: null

Whether the list of items is still loading.

### `isSingle`

- Type: Boolean
- Default: null

Restrict selections to one item.

### `list`

- Type: Array
  - id: Number
  - name: String
- Default: null

A complete list of item objects, each with id, name properties. This is displayed as a
clickable/keyboard-able list, and possibly filtered by the search term (searches name).

### `messages`

- Type: Object
  - clear: String - A more detailed label for the "Clear all" button, read to screen reader users.
  - list: String - Label for the list of selectable items, only read to screen reader users.
  - noItems: String - Message to display when the list is empty (implies nothing loaded from the server
or parent component).
  - noResults: String - Message to display when no matching results are found. %s is the search term.
  - search: String - Label for the search input
  - selected: Function - Label for the selected items. This is actually a function, so that we can pass
through the count of currently selected items.
  - updated: String - Label indicating that search results have changed, read to screen reader users.
- Default: null

Messages displayed or read to the user. Configure these to reflect your object type.
See `defaultMessages` above for examples.

### `onChange`

- **Required**
- Type: Function
- Default: null

Callback fired when selected items change, whether added, cleared, or removed.
Passed an array of item objects (as passed in via props.list).

### `onSearch`

- Type: Function
- Default: null

Callback fired when the search field is used.

### `renderItem`

- Type: Function
- Default: null

Callback to render each item in the selection list, allows any custom object-type rendering.

### `selected`

- **Required**
- Type: Array
- Default: null

The list of currently selected items.

### `search`

- Type: String
- Default: null


### `setState`

- Type: Function
- Default: null


### `debouncedSpeak`

- Type: Function
- Default: null


### `instanceId`

- Type: Number
- Default: null


`SearchListItem` (component)
============================



Props
-----

### `className`

- Type: String
- Default: null

Additional CSS classes.

### `depth`

- Type: Number
- Default: `0`

Depth, non-zero if the list is hierarchical.

### `item`

- Type: Object
- Default: null

Current item to display.

### `isSelected`

- Type: Boolean
- Default: null

Whether this item is selected.

### `isSingle`

- Type: Boolean
- Default: null

Whether this should only display a single item (controls radio vs checkbox icon).

### `onSelect`

- Type: Function
- Default: null

Callback for selecting the item.

### `search`

- Type: String
- Default: `''`

Search string, used to highlight the substring in the item name.

### `showCount`

- Type: Boolean
- Default: `false`

Toggles the "count" bubble on/off.

