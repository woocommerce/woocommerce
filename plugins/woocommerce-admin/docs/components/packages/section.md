`H` (component)
===============

These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
(`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
heading level.







`Section` (component)
=====================

The section wrapper, used to indicate a sub-section (and change the header level context).



Props
-----

### `component`

- Type: One of type: func, string, bool
- Default: null

The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props
passed to Section are passed on to the component.

### `children`

- Type: ReactNode
- Default: null

The children inside this section, rendered in the `component`. This increases the context level for the next heading used.

