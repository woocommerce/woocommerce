# TextToolbarButton

TextToolbarButton is used in Toolbar for text buttons which show isToggled state.

Note: Gutenberg core has `ToolbarGroup` and `ToolbarButton` in progress. When these are available this component may not be needed.

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


