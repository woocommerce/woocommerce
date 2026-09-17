/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import type { Editor } from '@woocommerce/e2e-utils';

export const getProductsNameFromClassicTemplate = async ( page: Page ) => {
	const products = page.locator( '.woocommerce-loop-product__title' );
	return products.allTextContents();
};

export const getProductsNameFromProductCollection = async ( page: Page ) => {
	const products = page.locator(
		'.wp-block-woocommerce-product-collection .wp-block-post-title'
	);
	return products.allTextContents();
};

export const getProductCollectionQuery = async ( page: Page ) =>
	page.evaluate( () => {
		const block = window.wp.data
			.select( 'core/block-editor' )
			.getBlocks()
			.find(
				( candidate: { name: string } ) =>
					candidate.name === 'woocommerce/product-collection'
			);

		return block?.attributes.query ?? {};
	} );

export const productQueryInnerBlocksTemplate = [
	{
		name: 'core/post-template',
		attributes: {
			__woocommerceNamespace:
				'woocommerce/product-query/product-template',
		},
		innerBlocks: [
			{ name: 'woocommerce/product-image' },
			{
				name: 'core/post-title',
				attributes: {
					__woocommerceNamespace:
						'woocommerce/product-query/product-title',
				},
			},
			{ name: 'woocommerce/product-price' },
			{ name: 'woocommerce/product-button' },
		],
	},
	{ name: 'core/query-pagination' },
	{ name: 'core/query-no-results' },
];

export const insertProductsQuery = async (
	editor: Editor,
	options: {
		inherit?: boolean;
	} = {}
) => {
	await editor.insertBlock( {
		name: 'core/query',
		attributes: {
			namespace: 'woocommerce/product-query',
			query: {
				inherit: options.inherit ?? true,
				postType: 'product',
			},
		},
		innerBlocks: productQueryInnerBlocksTemplate,
	} );
};
