# WooPaymentGatewayConfigure Slot & Fill

A Slotfill component that will replace the <DynamicForm /> component involved in displaying the form while adding a gateway via the payment task.

## Usage

```jsx
<WooPaymentGatewayConfigure id={ key }>
  {({defaultForm: DefaultForm}) => {
    return <>
      <p>
        Fill Content
      </p>
      { defaultForm }
    </>;
}}
</WooPaymentGatewayConfigure>

<WooPaymentGatewayConfigure.Slot id={ key } />
```

### WooPaymentGatewayConfigure (fill)

This is the fill component. You must provide the `id` prop to identify the slot that this will occupy. If you provide a function as the child of your fill (as shown above), you will receive some helper props to assist in creating your fill:

| Name             | Type     | Description                                                                                                              |
| ---------------- | -------- | ------------------------------------------------------------------------------------------------------------------------ |
| `defaultForm`    | Element  | The default instance of the <DynamicForm> component. Can overwrite props using React.cloneElement(defaultForm, newProps) |
| `defaultSubmit`  | Function | The default submit handler that is provided to the <Form> component                                                      |
| `defaultFields`  | Array    | An array of the field configuration objects provided by the API                                                          |
| `markConfigured` | Function | A helper function that will mark your gateway as configured                                                              |
| `paymentGateway` | Object   | An object describing all of the relevant data pertaining to this payment gateway                                         |

### WooPaymentGatewayConfigure.Slot (slot)

This is the slot component, and will not be used as frequently. It must also receive the required `id` prop that will be identical to the fill `id`.

| Name        | Type   | Description                                                                        |
| ----------- | ------ | ---------------------------------------------------------------------------------- |
| `fillProps` | Object | The props that will be provided to the fills, by default these are described above |
