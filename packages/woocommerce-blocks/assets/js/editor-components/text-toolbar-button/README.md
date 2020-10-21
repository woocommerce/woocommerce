# TextToolbarButton

TextToolbarButton is used in Toolbar for text buttons which show `isToggled` state.

Notes: 

- Gutenberg core has `ToolbarGroup` and `ToolbarButton` in progress. When these are available this component may not be needed.
- Gutenberg [core `html` block uses regular `Button` in toolbar](https://github.com/WordPress/gutenberg/blob/master/packages/block-library/src/html/edit.js), and sets `is-active` class to trigger "active" styling when button is toggled on.

## Usage

Example: two text buttons to select edit modes for cart block.

```jsx
<BlockControls>
  <Toolbar>
    <TextToolbarButton
      onClick={ toggleFullCartMode }
      isToggled={ isFullCartMode }>
      {  __(
        'Full Cart',
        'woo-gutenberg-products-block'
      ) }
    </TextToolbarButton>
    <TextToolbarButton
      onClick={ toggleFullCartMode }
      isToggled={ ! isFullCartMode }>
      {  __(
        'Empty Cart',
        'woo-gutenberg-products-block'
      ) }
    </TextToolbarButton>
  </Toolbar>
</BlockControls>
```


