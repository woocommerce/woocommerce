Tag
===

This component can be used to show an item styled as a "tag", optionally with an `X` + "remove"
or with a popover that is shown on click.



## Usage

```jsx
<Tag label="My tag" id={ 1 } />
<Tag label="Removable tag" id={ 2 } remove={ noop } />
<Tag label="Tag with popover" popoverContents={ ( <p>This is a popover</p> ) } />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`id` | One of type: number, string | `null` | The ID for this item, used in the remove function
`label` | String | `null` | (required) The name for this item, displayed as the tag's text
`popoverContents` | ReactNode | `null` | Contents to display on click in a popover
`remove` | Function | `null` | A function called when the remove X is clicked. If not used, no X icon will display
`screenReaderLabel` | String | `null` | A more descriptive label for screen reader users. Defaults to the `name` prop
