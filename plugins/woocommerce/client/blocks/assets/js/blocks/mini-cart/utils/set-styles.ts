function getClosestColor(
	element: Element | null,
	colorType: 'color' | 'backgroundColor'
): string | null {
	if ( ! element ) {
		return null;
	}
	const color = window.getComputedStyle( element )[ colorType ];
	if ( color !== 'rgba(0, 0, 0, 0)' && color !== 'transparent' ) {
		const matches = color.match( /\d+/g );
		if ( ! matches || matches.length < 3 ) {
			return null;
		}
		const [ r, g, b ] = matches.slice( 0, 3 );
		return `rgb(${ r }, ${ g }, ${ b })`;
	}
	return getClosestColor( element.parentElement, colorType );
}

let miniCartStyleSheet: CSSStyleSheet | null = null;

let storedBodyBackgroundColor: string | null = null;
let storedBadgeTextColor: string | null = null;
let storedBadgeBackgroundColor: string | null = null;

/**
 * Generates the CSS rules for mini-cart styles.
 */
function generateStyleRules(): string {
	return `
		div:where(.wp-block-woocommerce-mini-cart-contents) {
			background-color: ${ storedBodyBackgroundColor };
		}
		span:where(.wc-block-mini-cart__badge) {
			background-color: ${ storedBadgeBackgroundColor };
			color: ${ storedBadgeTextColor };
		}
	`;
}

/**
 * Re-applies mini-cart styles. This is needed because after iAPI navigation,
 * stylesheet rules may stop being applied correctly.
 */
export function reapplyStyles(): void {
	if ( ! miniCartStyleSheet || ! storedBadgeTextColor ) {
		return;
	}

	miniCartStyleSheet.replaceSync( generateStyleRules() );
}

function setStyles() {
	/**
	 * Get the background color of the body then set it as the background color
	 * of the Mini-Cart Contents block.
	 *
	 * We only set the background color, instead of the whole background. As
	 * we only provide the option to customize the background color.
	 */
	storedBodyBackgroundColor = getComputedStyle(
		document.body
	).backgroundColor;

	// For simplicity, we only consider the background color of the first Mini-Cart button.
	const firstMiniCartButton = document.querySelector(
		'.wc-block-mini-cart__button'
	);
	storedBadgeTextColor =
		getClosestColor( firstMiniCartButton, 'backgroundColor' ) || '#fff';
	storedBadgeBackgroundColor =
		getClosestColor( firstMiniCartButton, 'color' ) || '#000';

	miniCartStyleSheet = new CSSStyleSheet();
	miniCartStyleSheet.replaceSync( generateStyleRules() );

	document.adoptedStyleSheets = [
		...document.adoptedStyleSheets,
		miniCartStyleSheet,
	];
}

export default setStyles;
