/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'woocommerce/customer-account',
	selectors: {
		frontend: {
			icon: 'svg',
			label: '.label',
		},
		editor: {
			iconOptions: '.customer-account-display-style select',
			iconToggle: '.wc-block-customer-account__icon-style-toggle',
		},
	},
};

const publishAndVisitPost = async ( { page, editor } ) => {
	await editor.publishPost();
	const url = new URL( page.url() );
	const postId = url.searchParams.get( 'post' );
	await page.goto( `/?p=${ postId }`, { waitUntil: 'commit' } );
};

const selectTextOnlyOption = async ( { page } ) => {
	await page
		.locator( blockData.selectors.editor.iconOptions )
		.selectOption( 'Text-only' );

	page.locator( blockData.selectors.editor.iconToggle );
};

const selectIconOnlyOption = async ( { page } ) => {
	await page
		.locator( blockData.selectors.editor.iconOptions )
		.selectOption( 'Icon-only' );

	page.locator( blockData.selectors.editor.iconToggle );
};

const selectIconAndTextOption = async ( { page } ) => {
	await page
		.locator( blockData.selectors.editor.iconOptions )
		.selectOption( 'Icon and text' );

	page.locator( blockData.selectors.editor.iconToggle );
};

test.describe( `${ blockData.name } Block`, () => {
	test( 'Icon Options can be set to Text-only', async ( {
		admin,
		editor,
		page,
		frontendUtils,
	} ) => {
		await admin.createNewPost( { legacyCanvas: true } );
		await editor.insertBlock( { name: blockData.name } );

		await selectTextOnlyOption( { page } );

		await publishAndVisitPost( { page, editor } );

		const block = await frontendUtils.getBlockByName( blockData.name );

		await expect(
			block.locator( blockData.selectors.frontend.label )
		).toBeVisible();
		await expect(
			block.locator( blockData.selectors.frontend.icon )
		).toBeHidden();
	} );

	test( 'Icon Options can be set to Icon-only', async ( {
		admin,
		editor,
		page,
		frontendUtils,
	} ) => {
		await admin.createNewPost( { legacyCanvas: true } );
		await editor.insertBlock( { name: blockData.name } );

		await selectIconOnlyOption( { page } );

		await publishAndVisitPost( { page, editor } );

		const block = await frontendUtils.getBlockByName( blockData.name );

		await expect(
			block.locator( blockData.selectors.frontend.label )
		).toBeHidden();
		await expect(
			block.locator( blockData.selectors.frontend.icon )
		).toBeVisible();
	} );

	test( 'Icon Options can be set to Icon and text', async ( {
		admin,
		editor,
		page,
		frontendUtils,
	} ) => {
		await admin.createNewPost( { legacyCanvas: true } );
		await editor.insertBlock( { name: blockData.name } );

		await selectIconAndTextOption( { page } );

		await publishAndVisitPost( { page, editor } );

		const block = await frontendUtils.getBlockByName( blockData.name );

		await expect(
			block.locator( blockData.selectors.frontend.label )
		).toBeVisible();
		await expect(
			block.locator( blockData.selectors.frontend.icon )
		).toBeVisible();
	} );
} );
