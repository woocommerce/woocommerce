AnimationSlider
===

This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.
All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition

## Usage

```jsx
<AnimationSlider animationKey="1" animate="right">
	{ ( status ) => (
		<span>One (1)</span>
	) }
</AnimationSlider>
```

### Props

Name | Type | Default | Description
--- | --- | --- | ---
`children` | function | `null` | (required) A function returning rendered content with argument status, reflecting `CSSTransition` status
`animationKey` | any | `null` | (required) A unique identifier for each slideable page
`animate` | string | `null` | null, 'left', 'right', to designate which direction to slide on a change
`onExited` | function | `null` | A function to be executed after a transition is complete, passing the containing ref as the argument
