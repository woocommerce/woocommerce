/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import {
	__experimentalUseColorProps,
	__experimentalGetSpacingClassesAndStyles,
	__experimentalUseBorderProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { isFeaturePluginBuild } from '../settings/blocks/feature-flags';
import { isString, isObject } from '../types/type-guards';
import { hasSpacingStyleSupport } from '../utils/global-style';

type WithClass = {
	className: string;
};

type WithStyle = {
	style: Record< string, unknown >;
};

const parseStyle = ( style: unknown ): Record< string, unknown > => {
	if ( isString( style ) ) {
		return JSON.parse( style ) || {};
	}

	if ( isObject( style ) ) {
		return style;
	}

	return {};
};

export const useSpacingProps = ( attributes: unknown ): WithStyle => {
	if ( ! isFeaturePluginBuild() || ! hasSpacingStyleSupport() ) {
		return {
			style: {},
		};
	}
	const attributesObject = isObject( attributes ) ? attributes : {};
	const style = parseStyle( attributesObject.style );

	return __experimentalGetSpacingClassesAndStyles( {
		...attributesObject,
		style,
	} );
};

export const useTypographyProps = ( attributes: unknown ): WithStyle => {
	const attributesObject = isObject( attributes ) ? attributes : {};
	const style = parseStyle( attributesObject.style );
	const typography = isObject( style.typography )
		? ( style.typography as Record< string, unknown > )
		: {};

	return {
		style: {
			fontSize: attributesObject.fontSize || typography.fontSize,
			lineHeight: typography.lineHeight,
			fontWeight: typography.fontWeight,
			textTransform: typography.textTransform,
			fontFamily: attributesObject.fontFamily,
		},
	};
};

export const useColorProps = ( attributes: unknown ): WithStyle & WithClass => {
	if ( ! isFeaturePluginBuild() ) {
		return {
			className: '',
			style: {},
		};
	}

	const attributesObject = isObject( attributes ) ? attributes : {};
	const style = parseStyle( attributesObject.style );

	return __experimentalUseColorProps( { ...attributesObject, style } );
};

export const useBorderProps = (
	attributes: unknown
): WithStyle & WithClass => {
	if ( ! isFeaturePluginBuild() ) {
		return {
			className: '',
			style: {},
		};
	}

	const attributesObject = isObject( attributes ) ? attributes : {};
	const style = parseStyle( attributesObject.style );

	return __experimentalUseBorderProps( { ...attributesObject, style } );
};
