/**
 * External dependencies
 */
import { test, expect, getProductAttributeIds } from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

const blockData = {
	name: 'Filter by Attribute',
	slug: 'woocommerce/attribute-filter',
	urlSearchParamWhenFilterIsApplied: 'filter_size=small&query_type_size=or',
};

const getProductCollectionTitles = ( page: Page ) =>
	page.locator(
		'.wp-block-woocommerce-product-template .wp-block-post-title'
	);

test.describe( `${ blockData.name } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'attribute-filter',
				heading: 'Filter By Attribute',
			},
		} );
		const attributeFilter = await editor.getBlockByName( blockData.slug );

		await attributeFilter.getByText( 'Size' ).click();
		await attributeFilter.getByText( 'Done' ).click();
		await editor.openDocumentSettingsSidebar();
	} );

	test( 'edits the title, display style, and Apply behavior', async ( {
		page,
		editor,
	} ) => {
		const textSelector =
			'.wp-block-woocommerce-filter-wrapper .wp-block-heading';
		const title = 'New Title';

		await editor.canvas.locator( textSelector ).fill( title );
		await expect( editor.canvas.locator( textSelector ) ).toHaveText(
			title
		);

		const attributeFilter = await editor.getBlockByName( blockData.slug );
		await editor.selectBlocks( attributeFilter );
		await expect(
			attributeFilter.getByRole( 'checkbox', { name: 'Small' } )
		).toBeVisible();

		await page.getByLabel( 'DropDown' ).click();
		await expect(
			attributeFilter.getByRole( 'checkbox', { name: 'Small' } )
		).toBeHidden();
		await expect( attributeFilter.getByRole( 'combobox' ) ).toBeVisible();
		await expect(
			attributeFilter.getByRole( 'button', { name: 'Apply' } )
		).toBeHidden();

		await page.getByText( "Show 'Apply filters' button" ).click();
		await expect(
			attributeFilter.getByRole( 'button', { name: 'Apply' } )
		).toBeVisible();
	} );
} );

test.describe( `${ blockData.name } Block - with Product Collection`, () => {
	test( 'filters Product Collection automatically and defers changes until Apply when configured', async ( {
		page,
		createPost,
	} ) => {
		await page.clock.install();
		const { sizeAttributeId } = await getProductAttributeIds();
		const post = await createPost( 'filters-with-product-collection', {
			attributeId: sizeAttributeId,
		} );
		const productTitles = getProductCollectionTitles( page );

		await page.goto( post.link );
		await expect( productTitles.first() ).toBeVisible();
		const automaticBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		expect( automaticBaseline ).not.toHaveLength( 0 );
		await expect(
			page.getByRole( 'checkbox', { name: 'Small' } )
		).toBeVisible();

		await page.getByRole( 'checkbox', { name: 'Small' } ).click();
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'V-Neck T-Shirt' ] );

		const deferredPost = await createPost(
			'filters-with-product-collection',
			{ attributeId: sizeAttributeId, showFilterButton: true }
		);
		await page.goto( deferredPost.link );
		await expect( productTitles.first() ).toBeVisible();
		const smallCheckbox = page.getByRole( 'checkbox', { name: 'Small' } );
		await expect( smallCheckbox ).toBeVisible();
		await page.clock.pauseAt(
			( await page.evaluate( () => Date.now() ) ) + 1_000
		);
		const deferredBaseline = ( await productTitles.allTextContents() ).map(
			( title ) => title.trim()
		);
		expect( deferredBaseline ).not.toHaveLength( 0 );
		const deferredUrl = page.url();

		await smallCheckbox.click();
		await page.clock.runFor( 501 );
		await page.evaluate(
			() =>
				new Promise< void >( ( resolve ) => {
					const channel = new MessageChannel();
					channel.port1.addEventListener(
						'message',
						() => {
							channel.port1.close();
							channel.port2.close();
							resolve();
						},
						{ once: true }
					);
					channel.port1.start();
					channel.port2.postMessage( null );
				} )
		);
		await expect( smallCheckbox ).toBeChecked();
		const applyButton = page.getByRole( 'button', {
			name: 'Apply attribute filter',
		} );
		await expect( applyButton ).toBeVisible();
		await expect( applyButton ).toBeEnabled();
		await expect( page ).toHaveURL( deferredUrl );
		await expect( productTitles ).toHaveText( deferredBaseline );

		await page.clock.resume();
		await applyButton.click();
		await expect( page ).toHaveURL(
			new RegExp( blockData.urlSearchParamWhenFilterIsApplied )
		);
		await expect( productTitles ).toHaveText( [ 'V-Neck T-Shirt' ] );
	} );
} );
