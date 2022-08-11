/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { isObject } from '@woocommerce/types';
import { hasSpacingStyleSupport } from '@woocommerce/utils';
import { parseStyle } from '@woocommerce/base-utils';

type WithStyle = {
	style: Record< string, unknown >;
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
