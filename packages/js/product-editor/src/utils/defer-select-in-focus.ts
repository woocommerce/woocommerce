export function deferSelectInFocus( element: HTMLInputElement ) {
	// In some browsers like safari .select() function inside
	// the onFocus event doesn't work as expected because it
	// conflicts with onClick the first time user click the
	// input. Using setTimeout defers the text selection and
	// avoid the unexpected behaviour.
	setTimeout(
		function deferSelection( originalElement: HTMLInputElement ) {
			if ( element.ownerDocument.activeElement === originalElement ) {
				// We still have focus, so select the content.
				originalElement.select();
			}
		},
		0,
		element
	);
}
