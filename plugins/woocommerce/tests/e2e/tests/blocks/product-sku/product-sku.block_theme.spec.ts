/**
 * External dependencies
 */
import { randomUUID } from 'node:crypto';
import type { Page } from '@playwright/test';
import { expect, test } from '@woocommerce/e2e-utils';

type Finish = 'Matte' | 'Glossy';

const finishSelect = ( page: Page ) =>
	page.getByRole( 'combobox', { name: 'Finish', exact: true } );

const finishChip = ( page: Page, finish: Finish ) =>
	page
		.getByRole( 'radiogroup', { name: 'Finish', exact: true } )
		.getByRole( 'radio', { name: finish, exact: true } );

const addToCartBlocks = [
	{
		name: 'Add to Cart Form',
		blockName: 'woocommerce/add-to-cart-form',
		selectFinish: ( page: Page, finish: Finish ) =>
			finishSelect( page ).selectOption( { label: finish } ),
		clearFinish: ( page: Page ) => finishSelect( page ).selectOption( '' ),
	},
	{
		name: 'Add to Cart + Options',
		blockName: 'woocommerce/add-to-cart-with-options',
		selectFinish: ( page: Page, finish: Finish ) =>
			finishChip( page, finish ).click(),
		clearFinish: ( page: Page, finish: Finish ) =>
			finishChip( page, finish ).click(),
	},
];

test.describe( 'Product SKU block', () => {
	for ( const {
		name,
		blockName,
		selectFinish,
		clearFinish,
	} of addToCartBlocks ) {
		test( `shows the selected variation SKU with ${ name } when the parent has no SKU`, async ( {
			admin,
			cleanup,
			createHiddenProduct,
			editor,
			page,
			requestUtils,
		} ) => {
			const slug = `e2e-product-sku-${ randomUUID().slice( 0, 8 ) }`;
			const { id } = await createHiddenProduct( {
				name: 'Variable product without a parent SKU',
				slug,
				type: 'variable',
				attributes: [
					{
						name: 'Finish',
						variation: true,
						options: [ 'Matte', 'Glossy' ],
					},
				],
			} );
			const variationSku = `matte-${ id }`;
			await requestUtils.rest( {
				method: 'POST',
				path: `wc/v3/products/${ id }/variations/batch`,
				data: {
					create: [
						{
							regular_price: '10',
							sku: variationSku,
							attributes: [ { name: 'Finish', option: 'Matte' } ],
						},
						{
							regular_price: '10',
							attributes: [
								{ name: 'Finish', option: 'Glossy' },
							],
						},
					],
				},
			} );

			const template = await requestUtils.createTemplate( 'wp_template', {
				slug: `single-product-${ slug }`,
				title: `Product SKU with ${ name }`,
				content: `<!-- wp:${ blockName } /-->
<!-- wp:woocommerce/product-meta -->
<div class="wp-block-woocommerce-product-meta">
	<!-- wp:woocommerce/product-sku /-->
</div>
<!-- /wp:woocommerce/product-meta -->`,
			} );
			cleanup( () =>
				requestUtils.revertTemplate( 'wp_template', template.id )
			);

			const productUrl = `/?p=${ id }`;
			const skuBlock = page.locator(
				'.wp-block-woocommerce-product-sku'
			);
			const sku = skuBlock.locator( '.sku' );

			await test.step( 'hides a missing SKU by default', async () => {
				await page.goto( productUrl );
				await expect( sku ).toHaveText( '' );
				await expect( skuBlock ).toBeHidden();

				await selectFinish( page, 'Matte' );
				await expect( sku ).toBeVisible();
				await expect( sku ).toHaveText( variationSku );

				await selectFinish( page, 'Glossy' );
				await expect( skuBlock ).toBeHidden();

				await selectFinish( page, 'Matte' );
				await expect( sku ).toHaveText( variationSku );
				await clearFinish( page, 'Matte' );
				await expect( skuBlock ).toBeHidden();
			} );

			await test.step( 'shows N/A when the setting is enabled', async () => {
				await admin.visitSiteEditor( {
					postId: template.id,
					postType: 'wp_template',
					canvas: 'edit',
				} );
				await editor.openDocumentSettingsSidebar();
				await editor.selectBlocks(
					await editor.getBlockByName( 'woocommerce/product-sku' )
				);
				const showMissingSku = page.getByRole( 'checkbox', {
					name: 'Show N/A when the SKU is missing',
				} );
				await expect( showMissingSku ).not.toBeChecked();
				await showMissingSku.check();
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );

				await page.goto( productUrl );
				await expect( sku ).toBeVisible();
				await expect( sku ).toHaveText( 'N/A' );

				await selectFinish( page, 'Matte' );
				await expect( sku ).toHaveText( variationSku );
				await selectFinish( page, 'Glossy' );
				await expect( sku ).toHaveText( 'N/A' );
			} );
		} );
	}

	test( 'shows N/A for a simple product only when the setting is enabled', async ( {
		cleanup,
		createHiddenProduct,
		page,
		requestUtils,
	} ) => {
		const slug = `e2e-product-sku-${ randomUUID().slice( 0, 8 ) }`;
		const { id } = await createHiddenProduct( {
			name: 'Simple product without a SKU',
			slug,
			type: 'simple',
			regular_price: '10',
		} );
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: `single-product-${ slug }`,
			title: 'Product SKU for a simple product',
			content: `<!-- wp:woocommerce/product-meta -->
<div class="wp-block-woocommerce-product-meta">
	<!-- wp:woocommerce/product-sku /-->
	<!-- wp:woocommerce/product-sku {"showWhenUndefined":true} /-->
</div>
<!-- /wp:woocommerce/product-meta -->`,
		} );
		cleanup( () =>
			requestUtils.revertTemplate( 'wp_template', template.id )
		);

		await page.goto( `/?p=${ id }` );
		const skuBlock = page.locator( '.wp-block-woocommerce-product-sku' );
		await expect( skuBlock ).toHaveCount( 1 );
		const sku = skuBlock.locator( '.sku' );
		await expect( sku ).toBeVisible();
		await expect( sku ).toHaveText( 'N/A' );
	} );
} );
