export const checkOverflow = (
	scrollableElement: HTMLElement
): {
	overflowTop: boolean;
	overflowBottom: boolean;
	overflowLeft: boolean;
	overflowRight: boolean;
} => {
	// This is a threshold to allow for little remaining space when scrolling.
	// Browsers may return fractions of a pixel, so we need to account for that.
	const overflowThreshold = 3;
	if ( ! scrollableElement ) {
		return {
			overflowTop: false,
			overflowBottom: false,
			overflowLeft: false,
			overflowRight: false,
		};
	}
	const {
		scrollTop,
		scrollHeight,
		clientHeight,
		scrollLeft,
		scrollWidth,
		clientWidth,
	} = scrollableElement;

	return {
		overflowTop: scrollTop > overflowThreshold,
		overflowBottom:
			scrollTop + clientHeight < scrollHeight - overflowThreshold,
		overflowLeft: scrollLeft > overflowThreshold,
		overflowRight:
			scrollLeft + clientWidth < scrollWidth - overflowThreshold,
	};
};
