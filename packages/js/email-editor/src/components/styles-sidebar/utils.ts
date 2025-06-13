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
				styles.elements.heading || {},
				styles.elements[ headingLevel ] || {},
		  ] ) as EmailStyles )
		: ( {
				...defaultStyleObject,
				...( styles.elements.heading || {} ),
				...( styles.elements[ headingLevel ] || {} ),
		  } as EmailStyles );

export const getElementStyles = (
	styles: EmailStyles,
	element: string,
	headingLevel = 'heading',
	merge = false
): EmailStyles => {
	switch ( element ) {
		case 'text':
			return {
				typography: styles.typography,
				color: styles.color,
			} as EmailStyles;
		case 'heading':
			return getHeadingElementStyles(
				styles,
				headingLevel ?? 'heading',
				merge
			);
		default:
			return ( styles.elements[ element ] ||
				defaultStyleObject ) as EmailStyles;
	}
};
