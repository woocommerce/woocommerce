`Stepper` (component)
=====================

A stepper component to indicate progress in a set number of steps.

Props
-----

### `className`

- Type: String
- Default: null

Additional class name to style the component.

### `currentStep`

- **Required**
- Type: String
- Default: null

The current step's key.

### `steps`

- **Required**
- Type: Array
  - key: String - Key used to identify step.
  - label: String - Label displayed in stepper.
  - isComplete: Boolean - Optionally mark a step complete regardless of step index.
- Default: null

An array of steps used.

### `direction`

- Type: One of: 'horizontal', 'vertical'
- Default: `'horizontal'`

Direction of the stepper.

### `isPending`

- Type: Boolean
- Default: `false`

Optionally mark the current step as pending to show a spinner.

