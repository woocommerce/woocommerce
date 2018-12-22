`EmptyContent` (component)
==========================

A component to be used when there is no data to show.
It can be used as an opportunity to provide explanation or guidance to help a user progress.

Props
-----

### `title`

- **Required**
- Type: String
- Default: null

The title to be displayed.

### `message`

- Type: String
- Default: null

An additional message to be displayed.

### `illustration`

- Type: String
- Default: `'/empty-content.svg'`

The url string of an image path. Prefix with `/` to load an image relative to the plugin directory.

### `illustrationHeight`

- Type: Number
- Default: `400`

Height to use for the illustration.

### `illustrationWidth`

- Type: Number
- Default: `400`

Width to use for the illustration.

### `actionLabel`

- **Required**
- Type: String
- Default: null

Label to be used for the primary action button.

### `actionURL`

- Type: String
- Default: null

URL to be used for the primary action button.

### `actionCallback`

- Type: Function
- Default: null

Callback to be used for the primary action button.

### `secondaryActionLabel`

- Type: String
- Default: null

Label to be used for the secondary action button.

### `secondaryActionURL`

- Type: String
- Default: null

URL to be used for the secondary action button.

### `secondaryActionCallback`

- Type: Function
- Default: null

Callback to be used for the secondary action button.

### `className`

- Type: String
- Default: null

Additional CSS classes.

