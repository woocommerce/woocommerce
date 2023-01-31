// Wait until the initial element position is ready
export const waitUntilElementTopNotChange = (
	elSelector: string,
	cb: () => void,
	pollMs: number
) => {
	const initialElement = document.querySelector( elSelector );
	let lastInitialElementTop = initialElement?.getBoundingClientRect().top;

	const intervalId = setInterval( () => {
		const top = initialElement?.getBoundingClientRect().top;
		if ( lastInitialElementTop === top ) {
			cb();
			clearInterval( intervalId );
		}
		lastInitialElementTop = top;
	}, pollMs );

	return intervalId;
};

// Observer position changes of an element
export const observePositionChange = (
	selector: string,
	callback: () => void,
	pollMs: number
) => {
	const initialElement = document.querySelector(
		selector
	) as HTMLElement | null;
	let lastInitialElementTop = initialElement?.offsetTop;

	return setInterval( () => {
		const top = initialElement?.offsetTop;
		if ( lastInitialElementTop !== top ) {
			callback();
		}
		lastInitialElementTop = top;
	}, pollMs );
};

// Overwrite the default behavior of click event for the "Enable guided mode" button
export const bindEnableGuideModeClickEvent = (
	onClick: EventListenerOrEventListenerObject
) => {
	window.document
		.querySelector( '.wp-heading-inline + .page-title-action' )
		?.addEventListener( 'click', onClick );
};

// Add listener to product "Publish" button.
export const bindPublishClickEvent = (
	onClick: EventListenerOrEventListenerObject
) => {
	const publishButton = window.document.querySelector( '#publish' );

	if ( publishButton ) {
		publishButton.addEventListener( 'click', onClick );
	}

	return function unbind() {
		publishButton?.removeEventListener( 'click', onClick );
	};
};
