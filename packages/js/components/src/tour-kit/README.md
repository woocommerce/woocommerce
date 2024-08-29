# tour-kit

A WooCommerce Tour Kit variant based on [@automattic/tour-kit](https://github.com/Automattic/wp-calypso/blob/trunk/packages/tour-kit/README.md) for configurable guided tours. Contains some optional effects (like spotlight and overlay) that can be enabled/disabled depending on the desired use..

Uses [Popper.js](https://popper.js.org/) underneath (also customizable via tour configuration).

## Usage

A typical expected workflow builds around:

1. Define the criteria for showing a tour.
2. Define a configuration for the tour, passing along a handler for closing.
3. Render it (or not).

### Sample

```jsx
import { TourKit } from '@woocommerce/components';

function Tour() {
	// 1. Define the criteria for showing a tour:
	const [ showTour, setShowTour ] = useState( true );

	// 2. Define a configuration for the tour, passing along a handler for closing.
	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.render-step-near-me',
				},
				meta: {
					heading: 'Lorem ipsum dolor sit amet.',
					descriptions: {
						desktop: 'Lorem ipsum dolor sit amet.',
					},
					primaryButton: {
						text: 'Done',
					},
				},
			},
		],
		closeHandler: () => setShowTour( false ),
		options: {},
	};

	// 3. Render it (or not):

	if ( ! showTour ) {
		return null;
	}

	return <TourKit config={ config } />;
}
```

## Accessibility

### Keyboard Navigation

When a tour is rendered and focused, the following functionality exists:

-   Close the tour on `ESC` key (in minimized view)
-   Go to previous/next step on `left/right` arrow keys (in step view)

## Configuration

The main API for configuring a tour is the config object. See example usage and [types.ts](./types.ts) for the full definition.

`config.steps`: An array of objects that define the content we wish to render on the page. Each step defined by:

-   `referenceElements` (optional): A set of `desktop` & `mobile` selectors to render the step near.
-   `focusElement` (optional): A set of `desktop` & `mobile` & `iframe` selectors to automatically focus.
-   `meta`: Arbitrary object that encloses the content we want to render for each step.
-   `classNames` (optional): An array or CSV of CSS classes applied to a step.

`config.closeHandler`: The callback responsible for closing the tour.

-   `tourStep`: A React component that will be called to render each step. Receives the following properties:

    -   `steps`: The steps defined for the tour.
    -   `currentStepIndex`
    -   `onDismiss`: Handler that dismissed/closes the tour.
    -   `onNext`: Handler that progresses the tour to the next step.
    -   `onPrevious`: Handler that takes the tour to the previous step.
    -   `onMinimize`: Handler that minimizes the tour (passes rendering to `tourMinimized`).
    -   `setInitialFocusedElement`: A dispatcher that assigns an element to be initially focused when a step renders (see examples).
    -   `onGoToStep`: Handler that progresses the tour to a given step index.

-   `tourMinimized`: A React component that will be called to render a minimized view for the tour. Receives the following properties:
    -   `steps`: The steps defined for the tour.
    -   `currentStepIndex`
    -   `onDismiss`: Handler that dismissed/closes the tour.
    -   `onMaximize`: Handler that expands the tour (passes rendering to `tourStep`).

`config.options` (optional):

-   `classNames` (optional): An array or CSV of CSS classes to enclose the main tour frame with.

-   `effects`: An object to enable/disable/combine various tour effects:

    -   `spotlight`: Adds a semi-transparent overlay and highlights the reference element when provided with a transparent box over it. Expects an object with optional styles to override the default highlight/spotlight behavior when provided (default: spotlight wraps the entire reference element).
        -   `interactivity`: An object that configures whether the user is allowed to interact with the referenced element during the tour
        -   `styles`: CSS properties that configures the styles applied to the spotlight overlay
    -   `arrowIndicator`: Adds an arrow tip pointing at the reference element when provided.
    -   `overlay`: Includes the semi-transparent overlay for all the steps (also blocks interactions with the rest of the page)
    -   `autoScroll`: The page scrolls up and down automatically such that the step target element is visible to the user.

-   `callbacks`: An object of callbacks to handle side effects from various interactions (see [types.ts](./src/types.ts)).

-   `popperModifiers`: The tour uses Popper to position steps near reference elements (and for other effects). An implementation can pass its own modifiers to tailor the functionality further e.g. more offset or padding from the reference element.
-   `tourRating` (optional - only in WPCOM Tour Kit variant):

    -   `enabled`: Whether to show rating in last step.
    -   `useTourRating`: (optional) A hook to provide the rating from an external source/state (see [types.ts](./src/types.ts)).
    -   `onTourRate`: (optional) A callback to fire off when a rating is submitted.

-   `portalElementId`: A string that lets you customize under which DOM element the Tour will be appended.

`placement` (Optional) : Describes the preferred placement of the popper. Possible values are left-start, left, left-end, top-start, top, top-end, right-start, right, right-end, bottom-start, bottom, and bottom-end.
