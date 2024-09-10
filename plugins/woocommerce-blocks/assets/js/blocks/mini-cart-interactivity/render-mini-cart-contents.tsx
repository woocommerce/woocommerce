/**
 * External dependencies
 */
import { getRegisteredBlockComponents } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import { renderParentBlock } from '../../atomic/utils';
import { MiniCartContentsBlock } from './mini-cart-contents/block';
import { attributes as miniCartContentsAttributes } from '../mini-cart/mini-cart-contents/attributes';
import { getValidBlockAttributes } from '../../base/utils';
import '../mini-cart/mini-cart-contents/inner-blocks/register-components';

const generateUniqueId = () => {
	return (
		Date.now().toString() + Math.floor( Math.random() * 10000 ).toString()
	);
};

// We load this async so that we don't have to load the mini-cart block.
export const renderMiniCartContents = ( templateElement: HTMLDivElement ) => {
	const clonedTemplateNode = templateElement.cloneNode(
		true
	) as HTMLDivElement;

	// Generate a unique ID so that we can give a specific selector to renderParentBlock.
	const uniqueId = `mini-cart-template-${ generateUniqueId() }`;
	( clonedTemplateNode as HTMLElement ).id = uniqueId;

	templateElement.insertAdjacentElement(
		'afterend',
		clonedTemplateNode as HTMLDivElement
	);

	const root = renderParentBlock( {
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
		selector: `#${ uniqueId } .wp-block-woocommerce-mini-cart-contents`,
		blockMap: getRegisteredBlockComponents(
			'woocommerce/mini-cart-contents'
		),
	} );

	clonedTemplateNode.style.display = 'block';

	return root;
};
