/**
 * Recursively searches up the DOM tree to find the first non-transparent color of the specified type.
 *
 * @param element   - The DOM element to check for color.
 * @param colorType - Whether to check for color or background color.
 *
 * @return The computed RGB color string or null if not found.
 */
function getClosestColor(
	element: Element | null,
	colorType: 'color' | 'backgroundColor'
): string | null {
	if ( ! element ) {
		return null;
	}
	const computedColor = window.getComputedStyle( element )[ colorType ];

	// Skip transparent or default "empty" colors.
	if (
		computedColor !== 'rgba(0, 0, 0, 0)' &&
		computedColor !== 'transparent'
	) {
		// Extract RGB values from the color string.
		const rgbValues = computedColor.match( /\d+/g );

		if ( ! rgbValues || rgbValues.length < 3 ) {
			return null;
		}

		const [ red, green, blue ] = rgbValues.slice( 0, 3 );
		return `rgb(${ red }, ${ green }, ${ blue })`;
	}

	// If current element has transparent color, check parent element.
	return getClosestColor( element.parentElement, colorType );
}

/**
 * Sets the appropriate styles for Product Filter selector pills to ensure
 * visibility in both light and dark themes.
 *
 * This function swaps the text and background colors for selected pills
 * to create better contrast in all theme environments.
 */
function setStyles(): void {
	/**
	 * Get the background color of the body then set it as the background color
	 * of the Product Filter Selector selected pills.
	 *
	 * We only set the background color, instead of the whole background. As
	 * we only provide the option to customize the background color.
	 */

	// For simplicity, we only consider the background color of the first Product Filter Selector pills.
	const pillsContainer = document.querySelector(
		'.wc-block-product-filter-chips__items'
	);

	if ( ! pillsContainer ) {
		return;
	}

	const style = document.createElement( 'style' );

	const selectedPillColor =
		getClosestColor( pillsContainer, 'backgroundColor' ) || '#fff';
	const selectedPillBackgroundColor =
		getClosestColor( pillsContainer, 'color' ) || '#000';

	// We use :where here to reduce specificity so customized colors and theme CSS take priority.
	style.appendChild(
		document.createTextNode(
			`:where(.wc-block-product-filter-chips__item)[aria-checked="true"] {
				background-color: ${ selectedPillBackgroundColor };
				color: ${ selectedPillColor };
				border-color: ${ selectedPillBackgroundColor };
			}

			:where(.wc-block-product-filter-chips__item)[aria-checked="true"]:hover {
				background-color: color-mix(in srgb,${ selectedPillBackgroundColor } 85%,transparent)
			}`
		)
	);

	document.head.appendChild( style );
}

export default setStyles;
