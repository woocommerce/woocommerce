# TextToolbarButton <!-- omit in toc -->

TextToolbarButton is used in Toolbar for text buttons which show `isToggled` state.

Notes:

-   Gutenberg core has `ToolbarGroup` and `ToolbarButton` in progress. When these are available this component may not be needed.
-   Gutenberg [core `html` block uses regular `Button` in toolbar](https://github.com/WordPress/gutenberg/blob/master/packages/block-library/src/html/edit.js), and sets `is-active` class to trigger "active" styling when button is toggled on.

## Usage

Example: two text buttons to select edit modes for cart block.

```jsx
<BlockControls>
	<Toolbar>
		<TextToolbarButton
			onClick={ toggleFullCartMode }
			isToggled={ isFullCartMode }
		>
			{ __( 'Full Cart', 'woocommerce' ) }
		</TextToolbarButton>
		<TextToolbarButton
			onClick={ toggleFullCartMode }
			isToggled={ ! isFullCartMode }
		>
			{ __( 'Empty Cart', 'woocommerce' ) }
		</TextToolbarButton>
	</Toolbar>
</BlockControls>
```

<!-- FEEDBACK -->

---

[We're hiring!](https://woo.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/README.md)

<!-- /FEEDBACK -->
