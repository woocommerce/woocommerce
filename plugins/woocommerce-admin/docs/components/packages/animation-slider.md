`AnimationSlider` (component)
=============================

This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.
All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition

Props
-----

### `children`

- **Required**
- Type: Function
- Default: null

A function returning rendered content with argument status, reflecting `CSSTransition` status.

### `animationKey`

- **Required**
- Type: *
- Default: null

A unique identifier for each slideable page.

### `animate`

- Type: One of: null, 'left', 'right'
- Default: null

null, 'left', 'right', to designate which direction to slide on a change.

### `onExited`

- Type: Function
- Default: null

A function to be executed after a transition is complete, passing the containing ref as the argument.

