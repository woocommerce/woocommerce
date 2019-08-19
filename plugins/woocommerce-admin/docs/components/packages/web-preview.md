`WebPreview` (component)
========================

WebPreview component to display an iframe of another page.

Props
-----

### `className`

- Type: String
- Default: null

Additional class name to style the component.

### `loadingContent`

- Type: ReactNode
- Default: `<Spinner />`

Content shown when iframe is still loading.

### `onLoad`

- Type: Function
- Default: `noop`

Function to fire when iframe content is loaded.

### `src`

- **Required**
- Type: String
- Default: null

Iframe src to load.

### `title`

- **Required**
- Type: String
- Default: null

Iframe title.

