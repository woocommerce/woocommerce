/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __experimentalUseColorProps } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { isObject } from '@woocommerce/types';
import { parseStyle } from '@woocommerce/base-utils';

type WithClass = {
	className: string;
};

type WithStyle = {
	style: Record< string, unknown >;
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
