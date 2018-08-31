`Link` (component)
==================

Use `Link` to create a link to another resource. It accepts a type to automatically
create wp-admin links, wc-admin links, and external links.

Props
-----

### `href`

- **Required**
- Type: String
- Default: null

The resource to link to.

### `type`

- Type: One of: 'wp-admin', 'wc-admin', 'external'
- Default: `'wc-admin'`

Type of link. For wp-admin and wc-admin, the correct prefix is appended.

