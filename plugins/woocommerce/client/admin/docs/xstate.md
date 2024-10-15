# XState Dev Tooling

XState is a state management framework for Javascript applications. It is based on the concept of [finite state machines](https://en.wikipedia.org/wiki/Finite-state_machine) and statecharts.

In order to help you visualize and debug the state machines within WooCommerce Core, XState provides a [visualizer](https://stately.ai/viz) that you can use in your browser.


## Enabling the visualizer

To enable this, run this command in your browser's developer console:

```js
localStorage.setItem('xstate_inspect', 'true');
```

Then, a new tab with the XState visualizer should appear for pages that have state machines.

## Using the visualizer


### Main View

The main panel in the visualizer will show you the current state of the state machine. Current states are shown with a blue border, and available transitions are solid blue bubbles. If the events are simple without payload requirements, you can click on the transition bubbles to trigger the transition. Otherwise, you can use the 'Send event' button on the bottom right of the visualizer to send events to the state machine.


### State Tab

The context is the working memory that the state machine has access to. It is a plain Javascript object that can be modified by the state machine.

Within the 'State' tab, you can see the printouts of three objects: "Value", "Context", and "State".

The 'Value' object is simply the current state that the state machine is in, and it may be an object if there are nested state machines.

The 'Context' object is the current context of the state machine. It is a plain Javascript object that can be modified by the state machine. The context is used to store information that is relevant to the state machine, but not part of the state itself. For example, the context may contain information about the user, or the current page that the user is on.

The 'State' object is the current state of the state machine. It contains information about the current state, the events that led to the current state, as well as the history object which contains the previous states.

### Events Tab

The 'Events' tab shows the events that have occurred since the state machine was initialized. You can click on the events to see the payload of the events.

Clicking on 'Show built-in events' will include built-in events in the list. These are non-user events that are triggered by the state machine itself, such as invoked promises.

These events are useful for debugging, as they can help you understand what events have occurred and what the payload of the events are, and provides a similar functionality to Redux Dev Tools.

### Actors Tab

If there is more than one state machine active (e.g, there are multiple state machines or there are child state machines), you can select which one to inspect by clicking on the 'Actors' tab on the top right of the visualizer.


