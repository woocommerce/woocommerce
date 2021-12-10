/**
 * External dependencies
 */
import { WC_BLOCKS_BUILD_URL } from '@woocommerce/block-settings';
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { lazy } from '@wordpress/element';
/**
 * Internal dependencies
 */
import emptyMiniCartContentsMetadata from './empty-mini-cart-contents-block/block.json';
import filledMiniCartMetadata from './filled-mini-cart-contents-block/block.json';

// Modify webpack publicPath at runtime based on location of WordPress Plugin.
// eslint-disable-next-line no-undef,camelcase
__webpack_public_path__ = WC_BLOCKS_BUILD_URL;

registerCheckoutBlock( {
	metadata: filledMiniCartMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "mini-cart-contents-block/filled-cart" */ './filled-mini-cart-contents-block/frontend'
		)
	),
} );

registerCheckoutBlock( {
	metadata: emptyMiniCartContentsMetadata,
	component: lazy( () =>
		import(
			/* webpackChunkName: "mini-cart-contents-block/empty-cart" */ './empty-mini-cart-contents-block/frontend'
		)
	),
} );
