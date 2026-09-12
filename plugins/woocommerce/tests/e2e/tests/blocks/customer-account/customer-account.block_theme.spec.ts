/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'woocommerce/customer-account',
	selectors: {
		frontend: {
			icon: 'svg',
			label: '.label',
		},
	},
};

test.describe( `${ blockData.name } Block`, () => {
	test( 'Icon Options can be set to Icon-only', async ( {
		admin,
		editor,
		page,
		frontendUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.name } );
		await editor.insertBlock( { name: blockData.name } );

		const editorBlocks = await editor.getBlockByName( blockData.name );
		await expect( editorBlocks ).toHaveCount( 2 );
		await editorBlocks.nth( 1 ).click();

		const iconOptions = page.getByRole( 'combobox', {
			name: 'Icon options',
		} );
		await expect( iconOptions ).toHaveValue( 'icon_and_text' );
		await iconOptions.selectOption( { label: 'Icon-only' } );
		await expect( iconOptions ).toHaveValue( 'icon_only' );

		await editor.publishAndVisitPost();

		// We have specified the parent block name as 'main' to ensure that the
		// block is found within the main content area of the page and not the hooked block in the header.
		const blocks = await frontendUtils.getBlockByName(
			blockData.name,
			'main'
		);
		await expect( blocks ).toHaveCount( 2 );

		const defaultBlock = blocks.nth( 0 );
		await expect(
			defaultBlock.locator( blockData.selectors.frontend.label )
		).toBeVisible();
		await expect(
			defaultBlock.locator( blockData.selectors.frontend.icon )
		).toBeVisible();

		const iconOnlyBlock = blocks.nth( 1 );
		await expect(
			iconOnlyBlock.locator( blockData.selectors.frontend.label )
		).toHaveCount( 0 );
		await expect(
			iconOnlyBlock.locator( blockData.selectors.frontend.icon )
		).toBeVisible();
		await expect(
			iconOnlyBlock.getByRole( 'link', {
				name: 'My Account',
				exact: true,
			} )
		).toBeVisible();
	} );
} );
