function getClosestColor(
	element: Element | null,
	colorType: 'color' | 'backgroundColor'
): string | null {
	if ( ! element ) {
		return null;
	}
	const color = window.getComputedStyle( element )[ colorType ];
	if ( color !== 'rgba(0, 0, 0, 0)' && color !== 'transparent' ) {
		return color;
	}
	return getClosestColor( element.parentElement, colorType );
}

function setStyles() {
	/**
	 * Get the background color of the body then set it as the background color
	 * of the Mini-Cart Contents block.
	 *
	 * We only set the background color, instead of the whole background. As
	 * we only provide the option to customize the background color.
	 */
	const style = document.createElement( 'style' );
	const backgroundColor = getComputedStyle( document.body ).backgroundColor;
	// For simplicity, we only consider the background color of the first Mini-Cart button.
	const firstMiniCartButton = document.querySelector(
		'.wc-block-mini-cart__button'
	);
	const badgeTextColor =
		getClosestColor( firstMiniCartButton, 'backgroundColor' ) || '#fff';
	const badgeBackgroundColor =
		getClosestColor( firstMiniCartButton, 'color' ) || '#000';

	// We use :where here to reduce specificity so customized colors and theme
	// CSS take priority.
	style.appendChild(
		document.createTextNode(
			`:where(.wp-block-woocommerce-mini-cart-contents) {
				background-color: ${ backgroundColor };
			}
			:where(.wc-block-mini-cart__badge) {
				background-color: ${ badgeBackgroundColor };
				color: ${ badgeTextColor };
			}`
		)
	);

	document.head.appendChild( style );
}

export default setStyles;
