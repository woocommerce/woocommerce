/**
 * External dependencies
 */
import { getRegisteredBlockComponents } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import { renderParentBlock } from '../../atomic/utils';
import { MiniCartContentsBlock } from '../mini-cart/mini-cart-contents/block';
import { attributes as miniCartContentsAttributes } from '../mini-cart/mini-cart-contents/attributes';
import { getValidBlockAttributes } from '../../base/utils';
import '../mini-cart/mini-cart-contents/inner-blocks/register-components';

// We load this async so that we don't have to load the mini-cart block.
export const renderMiniCartContents = ( contentsNode: Element ) => {
	const container = contentsNode.querySelector(
		'.wp-block-woocommerce-mini-cart-contents'
	);

	if ( ! container ) {
		return;
	}

	renderParentBlock( {
		Block: MiniCartContentsBlock,
		blockName: 'woocommerce/mini-cart-contents',
		getProps: ( el: Element ) => {
			return {
				attributes: getValidBlockAttributes(
					miniCartContentsAttributes,
					/* eslint-disable @typescript-eslint/no-explicit-any */
					( el instanceof HTMLElement ? el.dataset : {} ) as any
				),
			};
		},
		selector: '.wp-block-woocommerce-mini-cart-contents',
		blockMap: getRegisteredBlockComponents(
			'woocommerce/mini-cart-contents'
		),
	} );
};
