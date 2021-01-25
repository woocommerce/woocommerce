# List

List component to display a list of items.

## Usage

```jsx
const listItems = [
	{
		title: 'List item title',
		description: 'List item description text',
	},
	{
		before: <Gridicon icon="star" />,
		title: 'List item with before icon',
		description: 'List item description text',
	},
	{
		before: <Gridicon icon="star" />,
		after: <Gridicon icon="chevron-right" />,
		title: 'List item with before and after icons',
		description: 'List item description text',
	},
	{
		title: 'Clickable list item',
		description: 'List item description text',
		onClick: () => alert( 'List item clicked' ),
	},
];

<List items={ listItems } />;
```

If you wanted a different format for the individual list item you can pass in a functional child:

```
<List items={ listItems } >
{
	(item, index) => <div className="woocommerce-list__item-inner">{item.title}</div>
}
</List>
```

### Props

| Name        | Type   | Default | Description                                  |
| ----------- | ------ | ------- | -------------------------------------------- |
| `className` | String | `null`  | Additional class name to style the component |
| `items`     | Array  | `null`  | (required) An array of list items            |

`items` structure:

-   `after`: ReactNode - Content displayed after the list item text.
-   `before`: ReactNode - Content displayed before the list item text.
-   `className`: String - Additional class name to style the list item.
-   `description`: String - Description displayed beneath the list item title.
-   `href`: String - Href attribute used in a Link wrapped around the item.
-   `onClick`: Function - Called when the list item is clicked.
-   `target`: String - Target attribute used for Link wrapper.
-   `title`: String - Title displayed for the list item.
