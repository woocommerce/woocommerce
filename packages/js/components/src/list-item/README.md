ListItem
===

This component adds basic styles to list items and handles adding a handle when inside a `Sortable` container.

## Usage

You can use this list item directly and add your child content inside.

```jsx
<ListItem>
    Your content
</ListItem>
```

Or you can add this inside a sortable container to automatically add handles.

```jsx
<Sortable>
    <ListItem>Item 1 with handle</ListItem>
    <ListItem>Item 2 with handle</ListItem>
</Sortable>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | JSX.Element \| JSX.Element[] \| string | `undefined` | The content to show inside the list item
`onDragEnd` | Function | `() => null` | A callback when an item is no longer being dragged
`onDragStart` | Function | `() => null` | A callback when an item starts being dragged
