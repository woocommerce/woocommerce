SplitButtonDropdown
===

This is a ButtonGroup with a primary action button and a menu hidden behind a Dropdown toggle button. The component expects to receive Buttons as children. The primary action button will be the first child, and the dropdown menu will contain the rest of the children.

## Usage

```jsx
    // Primary button variant (default)
    <SplitButtonDropdown>
        <Button onClick={ primaryActionHandler } text="Primary Action"></Button>
        <Button onClick={ secondaryActionHandler } text="Secondary Action"></Button>
        <Button onClick={ tertiaryActionHandler } text="Tertiary Action"></Button>
    </SplitButtonDropdown>
    // Secondary button variant, disabled
    <SplitButtonDropdown variant="secondary" disabled>
        <Button onClick={ primaryActionHandler } text="Primary Action"></Button>
        <Button onClick={ secondaryActionHandler } text="Secondary Action"></Button>
        <Button onClick={ tertiaryActionHandler } text="Tertiary Action"></Button>
    </SplitButtonDropdown>
```

### Props

In addition to those listed here, you can use any props accepted by Button and they will be passed along to _both_ the primary action button and the dropdown toggle button. Anything you wish to apply to just the primary button (especially text, label, icon, etc) should be put on the first child button directly.

Name | Type | Default | Description
--- | --- | --- | ---
`disabled` | boolean | `null` | As in Button, but will be applied to both the primary action button and the dropdown. Will override something specifically set on the first child button.
`variant` | String | "primary" | As in Button, but with a different default value. Will override a value specifically set on the first child button.
`className` | String | `null` | This will show up in the container element as well as the elements associated with the primary button, the dropdown toggle button, and the dropdown menu. Will override a value specifically set on the first child button.
`menuIcon` | Icon | `chevronDown` | Icon for the dropdown toggle button when the dropdown is collapsed.
`menuIconExpanded` | Icon | `chevronUp` | Icon for the dropdown toggle button when the dropdown is expanded.
