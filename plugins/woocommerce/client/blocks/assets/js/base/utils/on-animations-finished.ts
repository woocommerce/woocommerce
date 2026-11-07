/**
 * Calls `callback` once the finite animations and transitions on `element` and its descendants finish (looping ones
 * never do, so they're ignored), or right away when none run. Returns a function that cancels the pending callback.
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
