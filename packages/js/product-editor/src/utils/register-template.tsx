/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { registerBlockType, Template } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { Product } from '@woocommerce/data';

export const registerTemplate = ( {
	template,
}: {
	product?: Partial< Product >;
	template: Template[] | undefined;
} ) => {
	registerBlockType( 'woocommerce/product-template', {
		title: 'My Template Block',
		category: 'widgets',
		attributes: {},
		edit: () => {
			return createElement( InnerBlocks, {
				template,
				templateLock: false,
			} );
		},
	} );
};
