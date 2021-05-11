# WooRemotePaymentSettings Slot & Fill

A Slotfill component that will replace the <Settings /> component involved in displaying the form while adding a gateway via the payment task.

## Usage

```jsx
<WooRemotePaymentSettings id={ key }>
  {({defaultSettings: DefaultSettings}) => <p>Fill Content</p>}
</WooRemotePaymentSettings>

<WooRemotePaymentSettings.Slot id={ key } />
```

### WooRemotePaymentSettings (fill)

This is the fill component. You must provide the `id` prop to identify the slot that this will occupy. If you provide a function as the child of your fill (as shown above), you will receive some helper props to assist in creating your fill:

| Name              | Type      | Description                                                                                               |
| ----------------- | --------- | --------------------------------------------------------------------------------------------------------- |
| `defaultSettings` | Component | The default instance of the <SettingsForm> component. Any provided props will override the given defaults |
| `defaultSubmit`   | Function  | The default submit handler that is provided to the <Form> component                                       |
| `defaultFields`   | Array     | An array of the field configuration objects provided by the API                                           |
| `markConfigured`  | Function  | A helper function that will mark your gateway as configured                                               |

### WooRemotePaymentSettings.Slot (slot)

This is the slot component, and will not be used as frequently. It must also receive the required `id` prop that will be identical to the fill `id`.

| Name        | Type   | Description                                                                        |
| ----------- | ------ | ---------------------------------------------------------------------------------- |
| `fillProps` | Object | The props that will be provided to the fills, by default these are described above |
