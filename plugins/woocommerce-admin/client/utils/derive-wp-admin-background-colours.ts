/**
 * Helper function that lightens a colour by blending it
 * with some percentage of white
 */
function blendWithWhite( hex: string, alpha: number ) {
	let r = parseInt( hex.slice( 1, 3 ), 16 ),
		g = parseInt( hex.slice( 3, 5 ), 16 ),
		b = parseInt( hex.slice( 5, 7 ), 16 );

	// Blend with white
	r = Math.floor( ( 1 - alpha ) * 255 + alpha * r );
	g = Math.floor( ( 1 - alpha ) * 255 + alpha * g );
	b = Math.floor( ( 1 - alpha ) * 255 + alpha * b );

	// Convert to hex
	const newHex =
		'#' +
		r.toString( 16 ).padStart( 2, '0' ) +
		g.toString( 16 ).padStart( 2, '0' ) +
		b.toString( 16 ).padStart( 2, '0' );

	return newHex;
}

/**
 * wp-admin theme colour only include the main colour,
 * but in some applications we want to derive a complementary
 * background colour that's some percentage lighter than the
 * wp-admin theme colour. This is not doable in CSS as it involves
 * breaking down the hex colour code and then running calculations on it.
 * As of writing, CSS calc can only operate on individual numbers
 */
export const deriveWpAdminBackgroundColours = () => {
	const rootStyles = window.getComputedStyle( document.body );
	const wpAdminThemeColor = rootStyles
		.getPropertyValue( '--wp-admin-theme-color' )
		.trim();

	document.documentElement.style.setProperty(
		'--wp-admin-theme-color-background-04',
		blendWithWhite( wpAdminThemeColor, 0.04 )
	);

	document.documentElement.style.setProperty(
		'--wp-admin-theme-color-background-25',
		blendWithWhite( wpAdminThemeColor, 0.25 )
	);
};
