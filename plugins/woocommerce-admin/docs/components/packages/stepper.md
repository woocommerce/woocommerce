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
  - description: String - Description displayed beneath the label.
  - isComplete: Boolean - Optionally mark a step complete regardless of step index.
  - content: ReactNode - Content displayed when the step is active.
- Default: null

An array of steps used.

### `isVertical`

- Type: Boolean
- Default: `false`

If the stepper is vertical instead of horizontal.

### `isPending`

- Type: Boolean
- Default: `false`

Optionally mark the current step as pending to show a spinner.

