H
===

These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
(`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
heading level.

## Usage

```jsx
<div>
	<H>Header using a contextual level (h3)</H>
	<Section component="article">
		<p>This is an article component wrapper.</p>
		<H>Another header with contextual level (h4)</H>
		<Section component={ false }>
			<p>There is no wrapper component here.</p>
			<H>This is an h5</H>
		</Section>
	</Section>
</div>
```

Section
===

The section wrapper, used to indicate a sub-section (and change the header level context).

## Usage

See above

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`component` | One of type: func, string, bool | `null` | The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component
`children` | ReactNode | `null` | The children inside this section, rendered in the `component`. This increases the context level for the next heading used
