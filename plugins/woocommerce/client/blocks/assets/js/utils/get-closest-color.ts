const isFullyTransparent = ( color: string ): boolean =>
	color === 'transparent' ||
	/^rgba\(.*,\s*0(?:\.0+)?\s*\)$/.test( color ) ||
	/\/\s*0(?:\.0+)?%?\s*\)$/.test( color );

/**
 * Find the nearest non-transparent color on an element or its ancestors.
 *
 * rgb() and rgba() values are returned as opaque rgb(). Other syntaxes, such as
 * the oklab() that browsers return for color-mix(), are returned unchanged: they
 * are valid CSS colors, and reading their numbers as RGB channels gives nonsense.
 */
export function getClosestColor(
	element: Element | null,
	colorType: 'color' | 'backgroundColor'
): string | null {
	if ( ! element ) {
		return null;
	}

	const color = window.getComputedStyle( element )[ colorType ];

	if ( ! color || isFullyTransparent( color ) ) {
		return getClosestColor( element.parentElement, colorType );
	}

	const rgb = color.match(
		/^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)/
	);

	return rgb ? `rgb(${ rgb[ 1 ] }, ${ rgb[ 2 ] }, ${ rgb[ 3 ] })` : color;
}
