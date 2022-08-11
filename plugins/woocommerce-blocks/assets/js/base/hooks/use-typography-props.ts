/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { isObject } from '@woocommerce/types';
import { parseStyle } from '@woocommerce/base-utils';

type WithStyle = {
	style: Record< string, unknown >;
};

export const useTypographyProps = ( attributes: unknown ): WithStyle => {
	const attributesObject = isObject( attributes ) ? attributes : {};
	const style = parseStyle( attributesObject.style );
	const typography = isObject( style.typography )
		? ( style.typography as Record< string, unknown > )
		: {};

	return {
		style: {
			fontSize: attributesObject.fontSize
				? `var(--wp--preset--font-size--${ attributesObject.fontSize })`
				: typography.fontSize,
			lineHeight: typography.lineHeight,
			fontWeight: typography.fontWeight,
			textTransform: typography.textTransform,
			fontFamily: attributesObject.fontFamily,
		},
	};
};
