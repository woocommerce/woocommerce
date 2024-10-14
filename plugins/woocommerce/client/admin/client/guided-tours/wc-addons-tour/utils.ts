// Try to make popper element visible on the screen
export const scrollPopperToVisibleAreaIfNeeded = (
	popperBoundingRect: DOMRect
) => {
	// 8px is added for some extra spacing from the top admin bar
	const adminBarHeight =
		( document.getElementById( 'wpadminbar' )?.offsetHeight || 0 ) + 8;

	// check if element is cut from the top
	if ( popperBoundingRect.top < adminBarHeight ) {
		window.scrollBy( 0, popperBoundingRect.top - adminBarHeight );
	} else if (
		// check if element is cut from the bottom
		popperBoundingRect.bottom > window.innerHeight
	) {
		window.scrollBy( 0, popperBoundingRect.bottom - window.innerHeight );
	}
};
