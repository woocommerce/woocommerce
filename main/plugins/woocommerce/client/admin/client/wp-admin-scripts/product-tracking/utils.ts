/**
 * Attaches a click event listener to a parent element and calls a callback when one of the child elements,
 * specified by the list of queries, is clicked. This allows handling events for child elements that may not
 * exist in the DOM when the event listener is added.
 *
 * @param {string}        parentQuery query of the parent element.
 * @param {Array<Object>} children    array of event, child query and callback pairs.
 */
export function attachEventListenerToParentForChildren(
	parentQuery: string,
	children: Array< {
		eventName: 'click' | 'change';
		childQuery: string;
		callback: ( clickedElement: Element ) => void;
	} >
) {
	const parent = document.querySelector( parentQuery );

	if ( ! parent ) return;

	const eventListener = ( event: Event ) => {
		children.forEach( ( { eventName, childQuery, callback } ) => {
			if (
				event.type === eventName &&
				( event.target as Element ).matches( childQuery )
			) {
				callback( event.target as Element );
			}
		} );
	};

	children.forEach( ( { eventName } ) => {
		parent.addEventListener( eventName, eventListener );
	} );
}

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
