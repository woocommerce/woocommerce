/**
 * External dependencies
 */
import { useEffect, useState, flushSync } from '@wordpress/element';
// @ts-expect-error TS7016 Could not find a declaration file for module '@wordpress/priority-queue'
import { createQueue } from '@wordpress/priority-queue'; // eslint-disable-line

const blockPreviewQueue = createQueue();

/**
 * Renders a component at the next idle time.
 *
 * @param {*} props
 */
export function Async( { children, placeholder } ) {
	const [ shouldRender, setShouldRender ] = useState( false );

	// In the future, we could try to use startTransition here, but currently
	// react will batch all transitions, which means all previews will be
	// rendered at the same time.
	// https://react.dev/reference/react/startTransition#caveats
	// > If there are multiple ongoing Transitions, React currently batches them
	// > together. This is a limitation that will likely be removed in a future
	// > release.

	useEffect( () => {
		const context = {};
		blockPreviewQueue.add( context, () => {
			// Synchronously run all renders so it consumes timeRemaining.
			// See https://github.com/WordPress/gutenberg/pull/48238
			flushSync( () => {
				setShouldRender( true );
			} );
		} );
		return () => {
			blockPreviewQueue.cancel( context );
		};
	}, [] );

	if ( ! shouldRender ) {
		// eslint-disable-next-line @typescript-eslint/no-unsafe-return
		return placeholder;
	}

	// eslint-disable-next-line @typescript-eslint/no-unsafe-return
	return children;
}
