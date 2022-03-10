Stepper
===

A stepper component to indicate progress in a set number of steps.

## Usage

```jsx
const steps = [
	{
		key: 'first',
		label: 'First',
		description: 'Step item description',
		content: <div>First step content.</div>,
	},
	{
		key: 'second',
		label: 'Second',
		description: 'Step item description',
		content: <div>Second step content.</div>,
	},
];

<Stepper
	steps={ steps }
	currentStep="first"
	isPending={ true }
/>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `null` | Additional class name to style the component
`currentStep` | String | `null` | (required) The current step's key
`steps` | Array | `null` | (required) An array of steps used
`isVertical` | Boolean | `false` | If the stepper is vertical instead of horizontal
`isPending` | Boolean | `false` | Optionally mark the current step as pending to show a spinner

### `steps` structure

Array of step objects with properties:

- `key:` String - Key used to identify step.
- `label`: String - Label displayed in stepper.
- `description`: String - Description displayed beneath the label.
- `isComplete`: Boolean - Optionally mark a step complete regardless of step index.
- `content`: ReactNode - Content displayed when the step is active.