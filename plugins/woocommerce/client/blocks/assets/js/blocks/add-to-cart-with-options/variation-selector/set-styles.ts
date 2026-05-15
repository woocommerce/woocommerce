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
 * Sets theme contrast hints for variation selector chips.
 */
function setStyles(): void {
	const container = document.querySelector(
		'.wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute'
	);

	if ( ! container ) {
		return;
	}

	const style = document.createElement( 'style' );

	const selectedPillColor =
		getClosestColor( container, 'backgroundColor' ) || '#fff';
	const selectedPillBackgroundColor =
		getClosestColor( container, 'color' ) || '#000';

	// We use :where here to reduce specificity so customized colors and theme CSS take priority.
	style.appendChild(
		document.createTextNode(
			`:where(.wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute) {
				--wc-product-filters-background-color: ${ selectedPillColor };
				--wc-product-filters-text-color: ${ selectedPillBackgroundColor };
			}`
		)
	);

	document.head.appendChild( style );
}

export default setStyles;
