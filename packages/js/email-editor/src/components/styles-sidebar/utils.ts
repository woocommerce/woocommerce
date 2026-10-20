/**
 * External dependencies
 */
import deepmerge from 'deepmerge';

/**
 * Internal dependencies
 */
import { EmailStyles } from '../../store';

const defaultStyleObject = {
	typography: {},
	color: {},
};

/**
 * Gets combined element styles for a heading element.
 *
 * If merge is true, individual styles will be merged with the heading styles.
 * This should be false in the Editor UI so heading levels state "default" in the tools UI instead of using
 * values from the parent "heading" element.
 *
 * @param styles
 * @param headingLevel
 * @param merge
 */
export const getHeadingElementStyles = (
	styles: EmailStyles,
	headingLevel = 'heading',
	merge = false
): EmailStyles =>
	merge
		? ( deepmerge.all( [
				defaultStyleObject,
				styles.elements?.heading || {},
				styles.elements?.[ headingLevel ] || {},
			] ) as EmailStyles )
		: ( {
				...defaultStyleObject,
				...( styles.elements?.heading || {} ),
				...( styles.elements?.[ headingLevel ] || {} ),
			} as EmailStyles );

export const getElementStyles = (
	styles: EmailStyles,
	element: string,
	headingLevel = 'heading',
	merge = false
): EmailStyles => {
	let elementStyles: EmailStyles;
	switch ( element ) {
		case 'text':
			elementStyles = {
				typography: styles.typography,
				color: styles.color,
			} as EmailStyles;
			break;
		case 'heading':
			elementStyles = getHeadingElementStyles(
				styles,
				headingLevel ?? 'heading',
				merge
			);
			break;
		default:
			elementStyles = ( styles.elements?.[ element ] ||
				defaultStyleObject ) as EmailStyles;
	}

	// Ensure the `typography` and `color` objects are always available to
	// consumers so they can safely destructure them.
	return {
		...elementStyles,
		typography: elementStyles.typography ?? {},
		color: elementStyles.color ?? {},
	} as EmailStyles;
};

type FontFace = {
	fontStyle?: string;
	fontWeight?: string | number;
};

type FontFamily = {
	slug?: string;
	fontFamily?: string;
	fontFace?: FontFace[];
};

const PRESET_PREFIX = 'var:preset|font-family|';
const CSS_VAR_PREFIX = 'var(--wp--preset--font-family--';

/**
 * Gets the font family slug from a preset reference.
 *
 * @param fontFamily The font family value.
 * @return The slug, or undefined when the value is not a preset reference.
 */
const getFontFamilySlug = ( fontFamily: string | undefined ) => {
	if ( fontFamily?.startsWith( PRESET_PREFIX ) ) {
		return fontFamily.slice( PRESET_PREFIX.length );
	}
	if (
		fontFamily?.startsWith( CSS_VAR_PREFIX ) &&
		fontFamily.endsWith( ')' )
	) {
		return fontFamily.slice( CSS_VAR_PREFIX.length, -1 );
	}
	return undefined;
};

/**
 * Gets the font faces the Appearance control should offer for a font.
 *
 * Email fonts have no font faces, so core would offer every weight. The email theme lists the
 * weights each font can show, and a saved style or weight is kept so the control still shows it.
 *
 * @param fontFamilies The font families from the theme settings.
 * @param fontWeights  The comma-separated weights each font family can show, keyed by slug.
 * @param fontFamily   The selected font family value or preset reference.
 * @param current      The saved font style and weight.
 * @return The font faces, or undefined to let core offer every weight.
 */
export const getFontFamilyFaces = (
	fontFamilies: FontFamily[],
	fontWeights: Record< string, string > | undefined,
	fontFamily: string | undefined,
	current: FontFace
): FontFace[] | undefined => {
	const slug = getFontFamilySlug( fontFamily );
	const family =
		fontFamilies.find( ( item ) => item.fontFamily === fontFamily ) ??
		( slug
			? fontFamilies.find( ( item ) => item.slug === slug )
			: undefined );
	const weights = ( family?.slug ? fontWeights?.[ family.slug ] : '' )
		?.split( ',' )
		.map( ( weight ) => weight.trim() )
		.filter( Boolean );
	let faces: FontFace[];
	if ( family?.fontFace?.length ) {
		faces = family.fontFace;
	} else if ( weights?.length ) {
		faces = weights.flatMap( ( fontWeight ) => [
			{ fontStyle: 'normal', fontWeight },
			{ fontStyle: 'italic', fontWeight },
		] );
	} else {
		return undefined;
	}

	if ( ! current.fontStyle && ! current.fontWeight ) {
		return faces;
	}

	const fontStyle = current.fontStyle ?? 'normal';
	const fontWeight = String( current.fontWeight ?? '400' );
	const isOffered = faces.some(
		( face ) =>
			( face.fontStyle ?? 'normal' ) === fontStyle &&
			String( face.fontWeight ) === fontWeight
	);

	// First, because core skips single weights listed after a variable weight range.
	return isOffered ? faces : [ { fontStyle, fontWeight }, ...faces ];
};
