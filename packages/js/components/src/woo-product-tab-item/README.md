# WooProductTabItem Slot & Fill

A Slotfill component that will allow you to add a new tab to the product editor.

## Usage

```jsx
<WooProductTabItem id={ key } location="tab/general" order={ 2 } pluginId="test-plugin" tabProps={ { title: 'New tab', name: 'new-tab' } } >
        <Card>
          <CardBody>{ /* Tab content */ }</CardBody>
        </Card>
</WooProductTabItem>

<WooProductTabItem.Slot location="tab/general" />
```

### WooProductTabItem (fill)

This is the fill component. You must provide the `id` prop to identify your section fill with a unique string. This component will accept a series of props:

| Prop        | Type   | Description                                                                                                    |
| ----------- | ------ | -------------------------------------------------------------------------------------------------------------- |
| `id`        | String | A unique string to identify your fill. Used for configuiration management.                                     |
| `templates` | Array  | Array of name and order of which template slots it should be rendered in                                       |
| `pluginId`  | String | A unique plugin ID to identify the plugin/extension that this fill is associated with.                         |
| `tabProps`  | Object | An object containing tab props: name, title, className, disabled (see TabPanel.Tab from @wordpress/components) |
| `order`     | Number | (optional) This number will dictate the order that the sections rendered by a Slot will be appear.             |

### WooProductTabItem.Slot (slot)

This is the slot component. This will render all the registered fills that match the `template` prop.

| Name       | Type   | Description                                                                                          |
| ---------- | ------ | ---------------------------------------------------------------------------------------------------- |
| `template` | String | Unique to the location that the Slot appears, and must be the same as the one provided to any fills. |
