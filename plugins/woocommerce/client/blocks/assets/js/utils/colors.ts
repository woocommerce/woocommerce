export function getElementBackgroundColor( element: HTMLElement ): string {
	while ( element ) {
		const bgColor = window.getComputedStyle( element ).backgroundColor;

		if (
			bgColor &&
			bgColor !== 'rgba(0, 0, 0, 0)' &&
			bgColor !== 'transparent'
		) {
			return bgColor;
		}

		element = element.parentElement as HTMLElement;
	}

	// Return white as the default background color.
	return 'rgb(255, 255, 255)';
}
