/**
 * Calls `callback` when the CSS transitions and animations of `element` end, so that a script can wait for motion
 * instead of a fixed timeout. The callback runs immediately when nothing animates, such as in Jest, which has no Web
 * Animations API.
 *
 * Call it after the style change that starts the motion. If a transition turns around while the script waits, the
 * script waits for the new one too.
 *
 * @param element  The element that animates.
 * @param callback Runs one time, when no animation of the element is in progress.
 * @return A function that cancels the callback.
 */
export const whenAnimationsEnd = (
	element: Element | null | undefined,
	callback: () => void
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
						.getAnimations()
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
