export const checkOverflow = ( scrollableElement: HTMLElement ) => {
	const marginError = 3;
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
		overflowTop: scrollTop > marginError,
		overflowBottom: scrollTop + clientHeight < scrollHeight - marginError,
		overflowLeft: scrollLeft > marginError,
		overflowRight: scrollLeft + clientWidth < scrollWidth - marginError,
	};
};
