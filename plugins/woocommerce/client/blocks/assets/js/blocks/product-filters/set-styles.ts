/**
 * Internal dependencies
 */
import { getClosestColor } from './utils/get-closest-color';

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
