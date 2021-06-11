Card
===

A basic card component with a header. The header can contain a title, an action, and an `EllipsisMenu` menu.

## Usage

```jsx
<div>
	<Card title="Store Performance" description="Key performance metrics">
		<p>Your stuff in a Card.</p>
	</Card>
	<Card title="Inactive Card" isInactive>
		<p>This Card is grayed out and has no box-shadow.</p>
	</Card>
</div>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`action` | ReactNode | `null` | One "primary" action for this card, appears in the card header
`className` | String | `null` | Additional CSS classes
`description` | One of type: string, node | `null` | The description displayed beneath the title
`isInactive` | Boolean | `null` | Boolean representing whether the card is inactive or not
`menu` | (custom validator) | `null` | An `EllipsisMenu`, with filters used to control the content visible in this card
`title` | One of type: string, node | `null` | The title to use for this card
