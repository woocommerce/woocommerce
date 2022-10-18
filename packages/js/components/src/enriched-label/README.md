# EnrichedLabel

Use `EnrichedLabel` to create a label with a tooltip.

## Usage

```jsx
<EnrichedLabel
	label="My label"
	helpDescription="My description."
	moreUrl="https://woocommerce.com"
	tooltipLinkCallback={ () => alert( 'Learn More clicked' ) }
/>
```

### Props

| Name                  | Type     | Default | Description                                                             |
| --------------------- | -------- | ------- | ----------------------------------------------------------------------- |
| `helpDescription`     | String   | `null`  | Text that will be shown in the tooltip.                                 |
| `label`               | String   | `null`  | Text that will be shown in the label.                                   |
| `moreUrl`             | String   | `null`  | URL that will be added to the link `Learn More`, shown after the label. |
| `tooltipLinkCallback` | Function | `noop`  | Callback that will be triggered after clicking the `Learn More` link.   |
