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

const EMAIL_SAFE_FONT_FACES: FontFace[] = [
	{ fontStyle: 'normal', fontWeight: '400' },
	{ fontStyle: 'italic', fontWeight: '400' },
	{ fontStyle: 'normal', fontWeight: '700' },
	{ fontStyle: 'italic', fontWeight: '700' },
];

/**
 * Gets the font faces the Appearance control should offer for a font.
 *
 * Without font faces, core offers every weight, but most email fonts only come in regular and bold.
 * A saved style or weight is kept in the list so the control still shows it.
 *
 * @param fontFamilies The font families from the theme settings.
 * @param fontFamily   The selected font family value or preset reference.
 * @param current      The saved font style and weight.
 */
export const getFontFamilyFaces = (
	fontFamilies: FontFamily[],
	fontFamily: string | undefined,
	current: FontFace
): FontFace[] => {
	const slug = fontFamily?.match( /font-family(?:\||--)([\w-]+)/ )?.[ 1 ];
	const family = fontFamilies.find(
		( item ) =>
			item.fontFamily === fontFamily || ( !! slug && item.slug === slug )
	);
	const faces = family?.fontFace?.length
		? family.fontFace
		: EMAIL_SAFE_FONT_FACES;

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

	return isOffered ? faces : [ ...faces, { fontStyle, fontWeight } ];
};
