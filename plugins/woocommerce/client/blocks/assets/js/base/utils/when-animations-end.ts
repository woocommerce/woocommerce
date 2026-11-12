/**
 * Calls `callback` when the CSS transitions and animations of `element` end, so that a script can wait for motion
 * instead of a fixed timeout. The callback runs immediately when nothing animates, such as in Jest, which has no Web
 * Animations API.
 *
 * Call it after the style change that starts the motion. If a transition turns around while the script waits, the
 * script waits for the new one too.
 *
 * @param element          The element that animates.
 * @param callback         Runs one time, when no animation of the element is in progress.
 * @param options          Options.
 * @param options.subtree  Also wait for the animations of the element's descendants, such as rows that stagger in.
 * @return A function that cancels the callback.
 */
export const whenAnimationsEnd = (
	element: Element | null | undefined,
	callback: () => void,
	{ subtree = false }: { subtree?: boolean } = {}
): ( () => void ) => {
	let isCancelled = false;

	const waitForAnimations = () => {
		if ( isCancelled ) {
			return;
		}
		// Only animations with `playState` `running` or `paused` are in progress. A finished animation can still hold
		// its end state, and a cancelled one never ends, so neither must hold back the callback. A loop never ends.
		const animations =
			typeof element?.getAnimations === 'function'
				? element
						.getAnimations( { subtree } )
						.filter(
							( animation ) =>
								( animation.playState === 'running' ||
									animation.playState === 'paused' ) &&
								animation.effect?.getComputedTiming()
									.endTime !== Infinity
						)
				: [];
		if ( animations.length === 0 ) {
			callback();
			return;
		}
		// A cancelled animation rejects `finished`. It no longer animates, so it counts as ended.
		void Promise.all(
			animations.map( ( animation ) =>
				animation.finished.catch( () => undefined )
			)
		).then( waitForAnimations );
	};

	waitForAnimations();

	return () => {
		isCancelled = true;
	};
};

/**
 * Calls `callback` when the animations of all `elements` end. See `whenAnimationsEnd`.
 *
 * @param elements The elements that animate.
 * @param callback Runs one time, when no animation of any element is in progress.
 * @return A function that cancels the callback.
 */
export const whenAllAnimationsEnd = (
	elements: Element[],
	callback: () => void
): ( () => void ) => {
	let pending = elements.length;
	if ( pending === 0 ) {
		callback();
		return () => undefined;
	}
	const cancels = elements.map( ( element ) =>
		whenAnimationsEnd( element, () => {
			pending--;
			if ( pending === 0 ) {
				callback();
			}
		} )
	);
	return () => cancels.forEach( ( cancel ) => cancel() );
};
