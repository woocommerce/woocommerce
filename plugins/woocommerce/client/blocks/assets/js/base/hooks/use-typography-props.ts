/**
 * External dependencies
 */
import { isObject, isString } from '@woocommerce/types';
import type { Style as StyleEngineProperties } from '@wordpress/style-engine/src/types';

/**
 * Internal dependencies
 */
import type { StyleProps } from './use-style-props';

type blockAttributes = {
	style: StyleEngineProperties;
	// String identifier for the font size preset--not an absolute value.
	fontSize?: string | undefined;
	// String identifier for the font family preset, not the actual font family.
	fontFamily?: string | undefined;
};

export const useTypographyProps = ( props: blockAttributes ): StyleProps => {
	const typography = isObject( props.style.typography )
		? props.style.typography
		: {};
	const classNameFallback = isString( typography.fontFamily )
		? typography.fontFamily
		: '';
	const className = props.fontFamily
		? `has-${ props.fontFamily }-font-family`
		: classNameFallback;

	return {
		className,
		style: {
			fontSize: props.fontSize
				? `var(--wp--preset--font-size--${ props.fontSize })`
				: typography.fontSize,
			fontStyle: typography.fontStyle,
			fontWeight: typography.fontWeight,
			letterSpacing: typography.letterSpacing,
			lineHeight: typography.lineHeight,
			textDecoration: typography.textDecoration,
			textTransform: typography.textTransform,
		},
	};
};
