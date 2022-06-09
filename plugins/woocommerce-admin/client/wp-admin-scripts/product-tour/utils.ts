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

// Overwrite the default behavior of click event for the "Enable guided mode" button
export const bindEnableGuideModeClickEvent = (
	onClick: EventListenerOrEventListenerObject
) => {
	const enableGuideModeBtn = Array.from(
		window.document.querySelectorAll( '.page-title-action' )
	).find( ( el ) => el.textContent === 'Enable guided mode' );

	if ( enableGuideModeBtn ) {
		enableGuideModeBtn.addEventListener( 'click', onClick );
	}
};
