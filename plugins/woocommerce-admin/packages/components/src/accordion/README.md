# Accordion

This is an accordion that renders the children inside. It will toggle the panel's content when the title is clicked.

## Usage

```jsx
<Accordion>
	<AccordionPanel
		className="class-name"
		count={ 15 }
		title="Panel 1"
		initialOpen={ true }
	>
		<span>Panel 1 content</span>
	</AccordionPanel>
	<AccordionPanel
		className="class-name"
		count={ 20 }
		title="Panel 2"
		initialOpen={ false }
	>
		<span>Panel 2 content</span>
	</AccordionPanel>
</Accordion>
```

### Props

| Name        | Type   | Default | Description             |
| ----------- | ------ | ------- | ----------------------- |
| `className` | String | `null`  | Additional CSS classes. |

# AccordionPanel

A component designed for use inside of the `Accordion` component.
`AccordionPanel` is used to give the panel content an accessible wrapper.

## Usage

```jsx
<AccordionPanel
	className="class-name"
	count={ 15 }
	title="Panel 1"
	initialOpen={ true }
>
	<span>Panel 1 content</span>
</AccordionPanel>
```

### Props

| Name          | Type    | Default | Description                                                                          |
| ------------- | ------- | ------- | ------------------------------------------------------------------------------------ |
| `className`   | Array   | `null`  | A list of objects with data to set the panels.                                       |
| `count`       | Number  | `null`  | Number of unread elements in the panel that will be shown next to the panel's title. |
| `initialOpen` | Boolean | `true`  | Whether or not the panel will start open.                                            |
| `title`       | String  | `null`  | The panel title.                                                                     |
