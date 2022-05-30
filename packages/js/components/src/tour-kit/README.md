# tour-kit

A Woocommerce Tour Kit variant for configurable guided tours based on [@automattic/tour-kit](https://github.com/Automattic/wp-calypso/blob/trunk/packages/tour-kit/README.md). Contains some optional effects (like spotlight and overlay) that can be enabled/disabled depending on desired use.

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
          primaryButtonText: "Done"
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

- Close the tour on `ESC` key (in minimized view)
- Go to previous/next step on `left/right` arrow keys (in step view)

## Configuration

The main API for configuring a tour is the config object. See example usage and [types.ts](./types.ts) for the full definition.

`config.steps`: An array of objects that define the content we wish to render on the page. Each step defined by:

- `referenceElements` (optional): A set of `desktop` & `mobile` selectors to render the step near.
- `meta`: Arbitrary object that encloses the content we want to render for each step.
- `classNames` (optional): An array or CSV of CSS classes applied to a step.

`config.closeHandler`: The callback responsible for closing the tour.

`config.renderers` (omitted in the WPCOM Tour Kit variant):

`config.options` (optional):

- `classNames` (optional): An array or CSV of CSS classes to enclose the main tour frame with.

- `effects`: An object to enable/disable/combine various tour effects:

  - `spotlight`: Adds a semi-transparent overlay and highlights the reference element when provided with a transparent box over it. Expects an object with optional styles to override the default highlight/spotlight behavior when provided (default: spotlight wraps the entire reference element).
  - `arrowIndicator`: Adds an arrow tip pointing at the reference element when provided.
  - `overlay`: Includes the semi-transparent overlay for all the steps (also blocks interactions with the rest of the page)

- `callbacks`: An object of callbacks to handle side effects from various interactions.

- `popperModifiers`: The tour uses Popper to position steps near reference elements (and for other effects). An implementation can pass its own modifiers to tailor the functionality further e.g. more offset or padding from the reference element.
- `tourRating` (optional - only in WPCOM Tour Kit variant):
  - `enabled`: Whether to show rating in last step.
  - `useTourRating`: (optional) A hook to provide the rating from an external source/state.
  - `onTourRate`: (optional) A callback to fire off when a rating is submitted.

- `portalElementId`: A string that lets you customize under which DOM element the Tour will be appended.
