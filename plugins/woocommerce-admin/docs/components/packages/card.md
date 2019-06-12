`Card` (component)
==================

A basic card component with a header. The header can contain a title, an action, and an `EllipsisMenu` menu.

Props
-----

### `action`

- Type: ReactNode
- Default: null

One "primary" action for this card, appears in the card header.

### `className`

- Type: String
- Default: null

Additional CSS classes.

### `description`

- Type: One of type: string, node
- Default: null

The description displayed beneath the title.

### `isInactive`

- Type: Boolean
- Default: null

Boolean representing whether the card is inactive or not.

### `menu`

- Type: (custom validator)
- Default: null

An `EllipsisMenu`, with filters used to control the content visible in this card

### `title`

- Type: One of type: string, node
- Default: null

The title to use for this card.

