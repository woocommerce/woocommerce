SplitDropdown
===

This is a ButtonGroup with a primary action and a menu hidden behind a Dropdown toggle button. The component expects to receive elements (probably Buttons) as children. The primary action will be the first child, and the dropdown menu will contain the rest of the children, if any. If only one child is given to this component the dropdown toggle will not be rendered.

## Usage

```jsx
    // Primary button variant (default)
    <SplitDropdown>
        <Button onClick={ primaryActionHandler }>Primary Action</Button>
        <Button onClick={ secondaryActionHandler }>Secondary Action</Button>
        <Button onClick={ tertiaryActionHandler }>Tertiary Action</Button>
    </SplitDropdown>
    // Secondary button variant, disabled
    <SplitDropdown variant="secondary" disabled>
        <Button onClick={ primaryActionHandler }>Primary Action</Button>
        <Button onClick={ secondaryActionHandler }>Secondary Action</Button>
        <Button onClick={ tertiaryActionHandler }>Tertiary Action</Button>
    </SplitDropdown>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`disabled` | boolean | `null` | As in Button, but will be applied to both the primary action button and the dropdown. If truthy, will override something specifically set on the first child.
`variant` | String | "primary" | As in Button, but with a different default value. Will override a value specifically set on the first child button.
`className` | String | `null` | This will show up in the container element as well as the elements associated with the primary action, the dropdown toggle button, and the dropdown menu. Will override a value specifically set on the first child.
