# FlexBox
===

This component wraps its children in a container with a CSS flexbox layout.

## Usage

```jsx
<FlexBox flexDirection="row" columnGap="5" width="100%">
    <span style={ flexGrow: 0 }>4.</span>
    <span style={ flexGrow: 3 }>Review steps 1-3.</span>
    <span style={ flexGrow: 0, cursor: 'pointer' } onClick={ actionHandler }>
        <Icon icon={ check } />
    </span>
</FlexBox>;
```


### Default Styles

The default flexbox rules for a FlexBox's children are set by the component's stylesheet to `flex-grow: 1` and `flex-shrink: 1`, but can be overridden by more specific CSS.

```css
.woocommerce-flex-box__container > * {
    flex-grow: 1;
    flex-shrink: 1;
}
```

### Props

| Name            | Type     | Default | Description                                                                                                                    |
| --------------- | -------- | ------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `children`      | \*       | `null`  | A renderable component in which to pass this component's state and helpers. Generally a number of input or other form elements |
