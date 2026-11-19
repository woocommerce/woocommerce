// Each script bundle has its own copy of this module, and so its own listeners. They all see the same events.
let wasKeyPress = false;

if ( typeof document !== 'undefined' ) {
	document.addEventListener( 'keydown', () => ( wasKeyPress = true ), true );
	document.addEventListener(
		'pointerdown',
		() => ( wasKeyPress = false ),
		true
	);
}

/**
 * Whether the shopper's last input was a key press, such as typing in a field or Tab to leave it, rather than a click
 * or a tap. Content that a key press shows or hides applies at once, without motion.
 */
export const isKeyboardInput = (): boolean => wasKeyPress;
