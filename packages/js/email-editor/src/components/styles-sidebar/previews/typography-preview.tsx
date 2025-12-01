/**
 * Internal dependencies
 */
import { useEmailStyles } from '../../../hooks';
import { getElementStyles } from '../utils';

export default function TypographyPreview( {
	element,
	headingLevel,
}: {
	element: string;
	headingLevel: string;
} ) {
	const { styles } = useEmailStyles();
	const elementStyles = getElementStyles(
		styles,
		element,
		headingLevel,
		true
	);

	const {
		fontFamily,
		fontSize,
		fontStyle,
		fontWeight,
		lineHeight,
		letterSpacing,
		textDecoration,
		textTransform,
	} = elementStyles.typography;

	const textColor = elementStyles.color?.text || 'inherit';
	const background = elementStyles.color?.background || '#f0f0f0';

	const extraStyles =
		element === 'link'
			? {
					textDecoration: textDecoration ?? 'underline',
			  }
			: {};

	return (
		<div
			className="edit-site-typography-preview"
			style={ {
				fontFamily: fontFamily ?? 'serif',
				background,
				color: textColor,
				lineHeight,
				fontSize,
				fontStyle,
				fontWeight,
				letterSpacing,
				textDecoration,
				textTransform,
				...extraStyles,
			} }
		>
			Aa
		</div>
	);
}
