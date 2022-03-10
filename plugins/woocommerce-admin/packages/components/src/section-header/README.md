SectionHeader
===

A header component. The header can contain a title, actions via children, and an `EllipsisMenu` menu.

## Usage

```jsx
<SectionHeader title="Section title" />
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`className` | String | `null` | Additional CSS classes
`menu` | (custom validator) | `null` | An `EllipsisMenu`, with filters used to control the content visible in this card
`title` | One of type: string, node | `null` | (required) The title to use for this card
