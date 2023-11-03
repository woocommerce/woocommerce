# WooPaymentGatewaySetup Slot & Fill

A Slotfill component that will replace the <Stepper /> involved in the installation for a gateway via the payment task.

## Usage

```jsx
<WooPaymentGatewaySetup id={ key }>
  {({defaultStepper: DefaultStepper}) => <p>Fill Content</p>}
</WooPaymentGatewaySetup>

<WooPaymentGatewaySetup.Slot id={ key } />
```

### WooPaymentGatewaySetup (fill)

This is the fill component. You must provide the `id` prop to identify the slot that this will occupy. If you provide a function as the child of your fill (as shown above), you will receive some helper props to assist in creating your fill:

| Name                 | Type      | Description                                                                                          |
| -------------------- | --------- | ---------------------------------------------------------------------------------------------------- |
| `defaultStepper`     | Component | The default instance of the <Stepper> component. Any provided props will override the given defaults |
| `defaultInstallStep` | Object    | The object that describes the default step configuration for installation of the gateway             |
| `defaultConnectStep` | Object    | The object that describes the default step configuration for configuration of the gateway            |
| `paymentGateway`     | Object    | An object describing all of the relevant data pertaining to this payment gateway                     |

### WooPaymentGatewaySetup.Slot (slot)

This is the slot component, and will not be used as frequently. It must also receive the required `id` prop that will be identical to the fill `id`.

| Name        | Type   | Description                                                                        |
| ----------- | ------ | ---------------------------------------------------------------------------------- |
| `fillProps` | Object | The props that will be provided to the fills, by default these are described above |
