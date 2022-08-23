SelectControl
===

A component that allows searching and selection of one or more items, providing accessibility for item and menu interaction.

## Usage

`SelectControl` expects an array of item objects with `value` and `label` properties by default.  However, using the `itemToString` prop will allow you to pass a function to determine the label used and customize this object's shape.

```jsx
const [ selected, setSelected ] = useState< SelectedType >( [] );

const items = [
	{ value: 'item-1', label: 'Item 1' },
	{ value: 'item-2', label: 'Item 2' },
];

<SelectControl
    multiple
    items={ items }
    label="My select control"
    selected={ selected }
    onSelect={ ( item ) => item && setSelected( [ ...selected, item ] ) }
    onRemove={ () => setSelected( selected.filter( ( i ) => i !== item ) ) }
/>
```

By default, the menu will render selectable items based on the provided items, but by passing a child function you can determine the render of those items.

```jsx
<SelectControl
    label="Custom render"
    items={ items }
    selected={ selected }
    onSelect={ ( item ) => setSelectedItem( item ) }
    onRemove={ () => setSelectedItem( null ) }
>
    { ( {
        items,
        isOpen,
        highlightedIndex,
        getItemProps,
        getMenuProps,
    } ) => {
        return (
            <ul { ...getMenuProps() }>
                { isOpen && items.map( ( item, index: number ) => (
                    <li 
                        key={ `${ item.value }${ index }` }
                        { ...getItemProps() }
                    >
                        { item.label }
                    </li>
                ) ) }
            </ul>
        );
    } }
</SelectControl>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | Function | `( { items, highlightedIndex, getItemProps, getMenuProps, isOpen } ) => JSX.Element` | A function that renders the menu and menu items
`multiple` | Boolean | `false` | Whether the input should allow multiple selections or a single selection
`items` | Array | `undefined` | The items used in the dropdown as an array of objects with `value` and `label` properties
`label` | String | `undefined` | A string shown above the input
`itemToString` | Function | `( item ) => item.label` | A function used to determine how a selected item should be shown
`getFilteredItems` | Function | `( allItems, inputValue, selectedItems ) => allItems.filter( ( item ) => selectedItems.indexOf( item ) < 0 && item.label.toLowerCase().startsWith( inputValue.toLowerCase() ) )` | A function to determine how items should be filtered based on user input and previously selected items
`onInputChange` | Function | `() => null` | A callback that fires when the user input has changed
`onRemove` | Function | `() => null` | A callback that fires when a selected item has been removed
`onSelect` | Function | `() => null` | A callback that fires when an item has been selected
`selected` | Array or Item | `undefined` | An array of selected items or a single selected item
