# WooProductFieldItem Slot & Fill

A Slotfill component that will allow you to add a new field to a specific section in the product editor.

## Usage

```jsx
<WooProductFieldItem id={ key } section="details" order={ 2 } pluginId="test-plugin" >
  { () => {
    return (
      <TextControl
        label="Name"
        name={ `product-mvp-name` }
        placeholder="e.g. 12 oz Coffee Mug"
        value="Test Name"
        onChange={ () => console.debug( 'Changed!' ) }
      />
    );
} }
</WooProductFieldItem>

<WooProductFieldItem.Slot section="details" />
```

### WooProductFieldItem (fill)

This is the fill component. You must provide the `id` prop to identify your product field fill with a unique string. This component will accept a series of props:

| Prop       | Type   | Description                                                                                      |
| ---------- | ------ | ------------------------------------------------------------------------------------------------ |
| `id`       | String | A unique string to identify your fill. Used for configuration management.                        |
| `sections` | Array  | Contains an array of name and order values for which slots it should be rendered in.             |
| `pluginId` | String | A unique plugin ID to identify the plugin/extension that this fill is associated with.           |
| `order`    | Number | (optional) This number will dictate the order that the fields rendered by a Slot will be appear. |

### WooProductFieldItem.Slot (slot)

This is the slot component. This will render all the registered fills that match the `section` prop.

| Name      | Type   | Description                                                                                         |
| --------- | ------ | --------------------------------------------------------------------------------------------------- |
| `section` | String | Unique to the section that the Slot appears, and must be the same as the one provided to any fills. |
