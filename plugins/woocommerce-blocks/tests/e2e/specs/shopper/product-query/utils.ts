/**
 * External dependencies
 */
import { insertBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */

export const block = {
	name: 'Products (Beta)',
	slug: 'woocommerce/product-query',
	selectors: {
		editor: {
			inheritQueryFromTemplateSetting:
				"//label[text()='Inherit query from template']",
			popularFilter: '.woocommerce-product-query-panel__sort',
			advanceFilterOptionsButton:
				"button[aria-label='Advanced Filters options']",
		},
		frontend: {
			classicProductsListName: '.woocommerce-loop-product__title',
			productQueryProductsListName:
				'.wp-block-query .wp-block-post-title',
		},
	},
};

export const addProductQueryBlock = async () => {
	await insertBlock( block.name );
	await page.waitForNetworkIdle();
};

export const toggleInheritQueryFromTemplateSetting = async () => {
	const [ button ] = await page.$x(
		block.selectors.editor.inheritQueryFromTemplateSetting
	);
	await button.click();
};

export const getProductsNameFromClassicTemplate = async () => {
	const products = await page.$$(
		block.selectors.frontend.classicProductsListName
	);
	return Promise.all(
		products.map( ( el ) =>
			page.evaluate( ( node ) => node.textContent, el )
		)
	);
};

export const getProductsNameFromProductQuery = async () => {
	const products = await page.$$(
		block.selectors.frontend.productQueryProductsListName
	);

	return Promise.all(
		products.map( ( el ) =>
			page.evaluate( ( element ) => element.textContent, el )
		)
	);
};
