/**
 * Set up fake timers for executing a function and restores them afterwards.
 *
 * @param fn Function to trigger.
 *
 * @return The result of the function call.
 */
export async function withFakeTimers< T >( fn: () => T ) {
	const usingFakeTimers = jest.isMockFunction( setTimeout );

	jest.setTimeout( 20000 );

	// Portions of the React Native Animation API rely upon these APIs. However,
	// Jest's 'legacy' fake timers mutate these globals, which breaks the Animated
	// API. We preserve the original implementations to restore them later.
	const requestAnimationFrameCopy = global.requestAnimationFrame;
	const cancelAnimationFrameCopy = global.cancelAnimationFrame;

	if ( ! usingFakeTimers ) {
		jest.useFakeTimers( {
			now: new Date( 2023, 9, 15 ),
			doNotFake: [ 'setTimeout' ],
		} );
	}

	const result = await fn();

	if ( ! usingFakeTimers ) {
		jest.useRealTimers();

		global.requestAnimationFrame = requestAnimationFrameCopy;
		global.cancelAnimationFrame = cancelAnimationFrameCopy;
	}
	return result;
}
