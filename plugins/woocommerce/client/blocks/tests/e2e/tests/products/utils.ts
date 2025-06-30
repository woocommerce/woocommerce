/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import type { Editor } from '@woocommerce/e2e-utils';

export const getProductsNameFromClassicTemplate = async ( page: Page ) => {
	const products = page.locator( '.woocommerce-loop-product__title' );
	return products.allTextContents();
};

export const getProductsNameFromProductQuery = async ( page: Page ) => {
	const products = page.locator( '.wp-block-query .wp-block-post-title' );
	return products.allTextContents();
};

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

export const insertProductsQuery = async ( editor: Editor ) => {
	await editor.insertBlock( {
		name: 'core/query',
		attributes: {
			namespace: 'woocommerce/product-query',
			query: {
				inherit: true,
			},
		},
		innerBlocks: productQueryInnerBlocksTemplate,
	} );
};
