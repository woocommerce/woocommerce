/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { isObject } from '@woocommerce/types';
import { parseStyle } from '@woocommerce/base-utils';

type WithStyle = {
	style: Record< string, unknown >;
};

// @todo The @wordpress/block-editor dependency should never be used on the frontend of the store due to excessive side and its dependency on @wordpress/components
// @see https://github.com/woocommerce/woocommerce-blocks/issues/8071
export const useSpacingProps = ( attributes: unknown ): WithStyle => {
	if ( ! isFeaturePluginBuild() ) {
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
