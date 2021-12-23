/**
 * External dependencies
 */
import {
	switchUserToAdmin,
	openDocumentSettingsSidebar,
} from '@wordpress/e2e-test-utils';

import {
	visitBlockPage,
	saveOrPublish,
	selectBlockByName,
} from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Filter Products by Attribute',
	slug: 'woocommerce/attribute-filter',
	class: '.wc-block-attribute-filter',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );

		await expect( page ).toClick( 'span.woocommerce-search-list__item-name', { text: 'Capacity' } );
		//needed for attributes list to load correctly
		await page.waitFor(1000);

		await expect( page ).toClick( 'button', { text: 'Done' } );
		await page.waitForNetworkIdle();
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	it( 'renders correctly', async () => {
		expect(
			await page.$$eval(
				'.wc-block-attribute-filter-list li',
				( attributes ) => attributes.length
			)
			// our test data loads 2 for the Capacity attribute.
		).toBeGreaterThanOrEqual( 2 );
	} );

	describe( 'Attributes', () => {
		beforeEach( async () => {
			await openDocumentSettingsSidebar();
			await selectBlockByName( block.slug );
		} );

		it( "allows changing the block's title", async () => {
			const textareaSelector = `.wp-block[data-type="${ block.slug }"] textarea.wc-block-editor-components-title`;
			await expect( page ).toFill( textareaSelector, 'New Title' );
			await page.click(
				'.components-toolbar button[aria-label="Heading 6"]'
			);
			await expect(
				page
			).toMatchElement(
				`.wp-block[data-type="${ block.slug }"] h6 textarea`,
				{ text: 'New Title' }
			);
			await expect( page ).toFill(
				textareaSelector,
				'Filter by Capacity'
			);
			await page.click(
				'.components-toolbar button[aria-label="Heading 3"]'
			);
		} );

		it( 'can hide product count', async () => {
			await expect( page ).toMatchElement(
				'.wc-filter-element-label-list-count'
			);
			await expect( page ).toClick( 'label', { text: 'Product count' } );
			await expect( page ).not.toMatchElement(
				'.wc-filter-element-label-list-count'
			);
			// reset
			await expect( page ).toClick( 'label', { text: 'Product count' } );
		} );

		it( 'can toggle go button', async () => {
			await expect( page ).not.toMatchElement(
				'.wc-block-filter-submit-button'
			);
			await expect( page ).toClick( 'label', { text: 'Filter button' } );
			await expect( page ).toMatchElement(
				'.wc-block-filter-submit-button'
			);
			// reset
			await expect( page ).toClick( 'label', { text: 'Filter button' } );
		} );

		it( 'can switch attribute', async () => {
			await expect( page ).toClick( 'button', {
				text: 'Filter Products by Attribute',
			} );

			await expect( page ).toClick(
				'span.woocommerce-search-list__item-name',
				{
					text: 'Capacity',
				}
			);
			await page.waitForSelector(
				'.wc-block-attribute-filter-list:not(.is-loading)'
			);
			expect(
				await page.$$eval(
					'.wc-block-attribute-filter-list li',
					( reviews ) => reviews.length
				)
				// Capacity has only 2 attributes
			).toEqual( 2 );


			await expect( page ).toClick(
				'span.woocommerce-search-list__item-name',
				{
					text: 'Shade',
				}
			);
			//needed for attributes list to load correctly
			await page.waitFor(1000);

			// reset
			await expect( page ).toClick(
				'span.woocommerce-search-list__item-name',
				{
					text: 'Capacity',
				}
			);
			//needed for attributes list to load correctly
			await page.waitFor(1000);
		} );

		it( 'renders on the frontend', async () => {
			await saveOrPublish();
			const link = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( link, { waitUntil: 'networkidle2' } );
			await page.waitForSelector(
				'.wp-block-woocommerce-attribute-filter'
			);
			await expect( page ).toMatchElement(
				'.wp-block-woocommerce-attribute-filter h3',
				{
					text: 'Filter by Capacity',
				}
			);

			expect(
				await page.$$eval(
					'.wc-block-attribute-filter-list li',
					( reviews ) => reviews.length
				)
				// Capacity has only two attributes
			).toEqual( 2 );
		} );
	} );
} );
