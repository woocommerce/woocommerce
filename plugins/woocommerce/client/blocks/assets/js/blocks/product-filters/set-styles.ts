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

export default function setStyles(): void {
	const container = document.querySelector( '.wc-block-product-filters' );

	if ( ! container ) {
		return;
	}

	const bg = getClosestColor( container, 'backgroundColor' );
	const fg = getClosestColor( container, 'color' );

	if ( ! bg && ! fg ) {
		return;
	}

	const css = `.wc-block-product-filters {
		${ bg ? `--wc-product-filters-background-color: ${ bg };` : '' }
		${ fg ? `--wc-product-filters-text-color: ${ fg };` : '' }
	}`;

	// Use adoptedStyleSheets so styles survive iAPI client-side navigations.
	try {
		const sheet = new CSSStyleSheet();
		sheet.replaceSync( css );
		document.adoptedStyleSheets = [ ...document.adoptedStyleSheets, sheet ];
	} catch {
		const style = document.createElement( 'style' );
		style.appendChild( document.createTextNode( css ) );
		document.head.appendChild( style );
	}
}
