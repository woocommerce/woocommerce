// Whether the shopper's last input was a key press rather than a pointer, and when they last pressed a pointer.
// Keyboard changes, such as arrowing through radio options, are repeated quickly, so they apply at once instead of
// animating. Each script bundle that includes this listens for itself, which is harmless: they all see the same input.
let isKeyboardInput = false;
let lastPointerAt = -Infinity;

if ( typeof document !== 'undefined' ) {
	document.addEventListener(
		'keydown',
		() => {
			isKeyboardInput = true;
		},
		true
	);
	document.addEventListener(
		'pointerdown',
		() => {
			isKeyboardInput = false;
			lastPointerAt = performance.now();
		},
		true
	);
}

export const isUsingKeyboard = () => isKeyboardInput;

/**
 * Whether the shopper pressed a pointer within `within` milliseconds, so a change now is most likely theirs (such
 * as clicking Pickup) rather than one that loading data makes, which shouldn't animate on page load.
 */
export const wasRecentPointerInput = ( within = 1000 ) =>
	! isKeyboardInput && performance.now() - lastPointerAt < within;
