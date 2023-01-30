/**
 * Recursive function that waits up to 3 seconds until an element is found, then calls the callback.
 *
 * @param {string}   query query of the element.
 * @param {Function} func  callback called when element is found.
 * @param {number}   tries used internally to limit the number of tries.
 */
export function waitUntilElementIsPresent(
	query: string,
	func: () => void,
	tries = 0
) {
	if ( tries > 6 ) {
		return;
	}
	setTimeout( () => {
		const element = document.querySelector( query );
		if ( element ) {
			func();
		} else {
			waitUntilElementIsPresent( query, func, ++tries );
		}
	}, 500 );
}
