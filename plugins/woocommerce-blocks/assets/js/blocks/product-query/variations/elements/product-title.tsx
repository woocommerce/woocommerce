/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { registerBlockVariation } from '@wordpress/blocks';
import {
	BLOCK_DESCRIPTION,
	BLOCK_ICON,
	BLOCK_TITLE,
} from '@woocommerce/atomic-blocks/product-elements/title/constants';

export const CORE_NAME = 'core/post-title';
export const VARIATION_NAME = 'woocommerce/product-query/product-title';

if ( isFeaturePluginBuild() ) {
	registerBlockVariation( CORE_NAME, {
		description: BLOCK_DESCRIPTION,
		name: VARIATION_NAME,
		title: BLOCK_TITLE,
		isActive: ( blockAttributes ) =>
			blockAttributes.__woocommerceNamespace === VARIATION_NAME,
		icon: {
			src: BLOCK_ICON,
		},
		attributes: {
			__woocommerceNamespace: VARIATION_NAME,
		},
		scope: [ 'block', 'inserter' ],
	} );
}
