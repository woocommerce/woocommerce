/**
 * Calls `callback` once the finite animations and transitions running on `element` and its descendants
 * have finished, or right away when none are running: reduced motion, missing styles, or jsdom.
 *
 * Looping animations, such as a spinner inside the element, are ignored because they never finish.
 *
 * @return A function that cancels the pending callback.
 */
export const onAnimationsFinished = (
	element: Element | null,
	callback: () => void
): ( () => void ) => {
	const animations = (
		element?.getAnimations?.( { subtree: true } ) ?? []
	).filter(
		( animation ) =>
			animation.effect?.getComputedTiming().iterations !== Infinity
	);
	if ( animations.length === 0 ) {
		callback();
		return () => undefined;
	}
	let isCancelled = false;
	void Promise.allSettled(
		animations.map( ( animation ) => animation.finished )
	).then( () => {
		if ( ! isCancelled ) {
			callback();
		}
	} );
	return () => {
		isCancelled = true;
	};
};
