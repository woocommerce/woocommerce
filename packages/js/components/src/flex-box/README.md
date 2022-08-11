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
| `children`      | JSX.Element | JSX.Element[] | | The items to be rendered in the flexbox layout. Override `flexGrow` and `flexShrink` as appropriate for your desired layout. |
| `className`     | string | `null`  | An additional className for the `FlexBox` container |
| **Style Properties:** will be used as inline styles on the `FlexBox` container if present. |
| flexDirection | string | 'row' | One of: 'row', 'row-reverse', 'column', 'column-reverse' |
| flexWrap | string | `null` | One of: 'nowrap', 'wrap', 'wrap-reverse' |
| justifyContent | string | `null` | One of: 'flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly' |
| alignItems | string | `null` | One of: 'flex-start', 'flex-end', 'center', 'baseline', 'stretch' |
| alignContent | string | `null` | One of: 'flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly', 'stretch' |
| rowGap | string or number | `null` | A CSS dimension string or a number, to which React will append "px". |
| columnGap | string or number | `null` | A CSS dimension string or a number, to which React will append "px". |
| width | string or number | `null` | A CSS dimension string or a number, to which React will append "px". |
| height | string or number | `null` | A CSS dimension string or a number, to which React will append "px". |
