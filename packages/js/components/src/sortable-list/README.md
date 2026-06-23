SortableList
===

`SortableList` renders data-backed sortable UI with mouse, touch, and keyboard support.

Use it when the source of truth is an array of data objects and the caller should receive the reordered array.

## Usage

```tsx
const [ items, setItems ] = useState( [
	{ id: 'card', label: 'Credit card' },
	{ id: 'cod', label: 'Cash on delivery' },
] );

<SortableList
	items={ items }
	onChange={ setItems }
	getItemId={ ( item ) => item.id }
	getItemLabel={ ( item ) => item.label }
>
	{ ( { items: renderedItems, getItemId } ) =>
		renderedItems.map( ( item ) => (
			<SortableListItem key={ item.id } id={ getItemId( item ) }>
				<SortableListDefaultHandle />
				{ item.label }
			</SortableListItem>
		) )
	}
</SortableList>;
```

## Fixed items

Use `getItemDisabled` when an item should render in place but should not be dragged or reordered.

```tsx
<SortableList
	items={ items }
	onChange={ setItems }
	getItemId={ ( item ) => item.id }
	getItemDisabled={ ( item ) => item.locked }
>
	{ ( { items: renderedItems, getItemId, getItemDisabled } ) =>
		renderedItems.map( ( item ) => (
			<SortableListItem
				key={ item.id }
				id={ getItemId( item ) }
				disabled={ getItemDisabled( item ) }
			>
				<SortableListDefaultHandle />
				{ item.label }
			</SortableListItem>
		) )
	}
</SortableList>;
```

## Payment settings style usage

```tsx
<SortableList
	className="woocommerce-list"
	items={ gateways }
	onChange={ setGateways }
	getItemId={ ( gateway ) => gateway.id }
	getItemLabel={ ( gateway ) => gateway.title }
>
	{ ( { items: renderedGateways, getItemId } ) =>
		renderedGateways.map( ( gateway ) => (
			<SortableListItem
				key={ gateway.id }
				id={ getItemId( gateway ) }
				className="woocommerce-list__item"
			>
				<div className="woocommerce-list__item-inner">
					<div className="woocommerce-list__item-before">
						<SortableListDefaultHandle />
						<img src={ gateway.icon } alt="" />
					</div>
					<div className="woocommerce-list__item-text">
						{ gateway.title }
					</div>
				</div>
			</SortableListItem>
		) )
	}
</SortableList>;
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`items` | `T[]` | required | Data items to sort.
`onChange` | `( items: T[] ) => void` | required | Called with the reordered array after a successful drop.
`getItemId` | `( item: T ) => UniqueIdentifier` | required | Returns each item's stable sortable ID.
`getItemDisabled` | `( item: T ) => boolean` | `() => false` | Returns whether an item is fixed in place and cannot be sorted.
`getItemLabel` | `( item: T ) => string` | `String( id )` | Returns the localized label used in screen-reader announcements.
`orientation` | `'vertical' \| 'horizontal'` | `'vertical'` | Sets the sorting strategy and axis restriction.
`instructions` | `string` | localized default | Screen-reader instructions for keyboard sorting.
`children` | `ReactNode \| render function` | `undefined` | Renders `SortableListItem` children.

