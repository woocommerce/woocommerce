export const checkOverflow = (
	scrollableElement: HTMLElement
): {
	top: boolean;
	bottom: boolean;
	left: boolean;
	right: boolean;
} => {
	// This is a threshold to allow for little remaining space when scrolling.
	// Browsers may return fractions of a pixel, so we need to account for that.
	const overflowThreshold = 3;
	if ( ! scrollableElement ) {
		return {
			top: false,
			bottom: false,
			left: false,
			right: false,
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
		top: scrollTop > overflowThreshold,
		bottom: scrollTop + clientHeight < scrollHeight - overflowThreshold,
		left: scrollLeft > overflowThreshold,
		right: scrollLeft + clientWidth < scrollWidth - overflowThreshold,
	};
};
