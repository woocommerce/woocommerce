/**
 * External dependencies
 */
import { paramCase as kebabCase } from 'change-case';

/**
 * Converts a color slug or custom color value into a CSS variable reference.
 *
 * @param {string} colorSlug Slug of the color.
 * @param {string} value     Value of the color.
 * @return {string} CSS variable value.
 */
export function getColorCSSVar(
	colorSlug: string | undefined,
	value: string | undefined = ''
): string {
	if ( colorSlug?.length ) {
		return `var(--wp--preset--color--${ colorSlug })`;
	}

	return value || '';
}

/**
 * Get custom key for a given color.
 *
 * @param {string} color Color name.
 * @return {string} Custom key.
 */
function getCustomKey( color: string ): string {
	return `custom${ color.charAt( 0 ).toUpperCase() }${ color.slice( 1 ) }`;
}

export function getStyleColorVars(
	prefix: string,
	attributes: Record< string, unknown >,
	colors: string[]
): Record< string, string > {
	const styleVars: Record< string, string > = {};

	colors.forEach( ( color ) => {
		const normalColor = attributes[ color ] as string | undefined;
		const customKey = getCustomKey( color );
		const customColor = attributes[ customKey ];

		if (
			( typeof normalColor === 'string' && normalColor.length > 0 ) ||
			( typeof customColor === 'string' && customColor.length > 0 )
		) {
			styleVars[ `--${ prefix }-${ kebabCase( color ) }` ] =
				getColorCSSVar( normalColor, customColor as string );
		}
	} );

	return styleVars;
}

export function getHasColorClasses(
	attributes: Record< string, unknown >,
	colors: string[]
): Record< string, string | undefined > {
	const cssClasses: Record< string, string | undefined > = {};

	colors.forEach( ( attr ) => {
		if ( ! attr.startsWith( 'custom' ) ) {
			const customAttr = getCustomKey( attr );

			/*
			 * Generate class name based on the attribute name,
			 * transforming from camelCase to kebab-case.
			 * Example: `warningTextColor` -> `has-warning-text-color`.
			 */
			const className = `has-${ kebabCase( attr ) }-color`;

			cssClasses[ className ] = ( attributes[ attr ] ||
				attributes[ customAttr ] ) as string;
		}
	} );

	return cssClasses;
}
