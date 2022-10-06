Sortable
===

This component provides a wrapper to allow dragging and sorting of items.

## Usage

This component accepts any valid JSX elements as children.  Adding a `key` to elements will provide a way to later identify the order of these elements in callbacks.

```jsx
<Sortable onOrderChange={ ( items ) => console.log( 'Items have been reordered:', items ) }>
    <div key="item-1">List item 1</div>
    <div key="item-2">List item 2</div>
    <div key="item-3">List item 3</div>
</Sortable>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | JSX.Element \| JSX.Element[] | `undefined` | The draggable items in the list
`isHorizontal` | boolean | `false` | Determines if the sortable container should be droppable horizontally
`onDragEnd` | Function | `() => null` | A callback when an item is no longer being dragged
`onDragOver` | Function | `() => null` | A callback when an item is being dragged over by another item
`onDragStart` | Function | `() => null` | A callback when an item starts being dragged
`onOrderChange` | Function | `() => null` | A callback when the order of the items has been updated
