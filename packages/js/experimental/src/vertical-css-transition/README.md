# VerticalCSSTransition

This is a wrapper to the [React CSSTransition](https://reactcommunity.org/react-transition-group/css-transition) component, allowing for each vertical height transitions (collapsing/expanding). CSS does not support height: auto transitions, this uses JavaScript instead.

## Usage

```jsx
<VerticalCSSTransition timeout={ 500 } in={ true } classNames="my-node" defaultStyle={ transitionProperty: 'max-height, opacity' }>
	<div>some content</div>
	<div>
		some more content <br /> line 2 <br /> line 3
	</div>
</VerticalCSSTransition>
```

```css
.my-node-enter {
	opacity: 0;
}

.my-node-enter-active {
	opacity: 1;
}
```

### Props

Props extends the [CSSTransition props](https://reactcommunity.org/react-transition-group/css-transition#CSSTransition-props).
Name | Type | Default | Description
--- | --- | --- | ---
`defaultStyle` | CSSProperties | `null` | Custom CSS properties for the transition component.

### defaultStyle

`defaultStyle` is used to extend the current list of CSS properties added by this component. It also allows you to add extra transition
properties by overwriting the `transitionProperty` property (see above for example).
