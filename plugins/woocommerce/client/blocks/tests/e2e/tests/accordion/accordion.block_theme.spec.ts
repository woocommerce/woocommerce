/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { AccordionPage } from './accordion.page';

const blockData = {
	slug: 'woocommerce/accordion-group',
};

const test = base.extend< { pageObject: AccordionPage } >( {
	pageObject: async (
		{ page, editor, frontendUtils, requestUtils },
		use
	) => {
		const pageObject = new AccordionPage( {
			page,
			editor,
			frontendUtils,
			requestUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( `${ blockData.slug } Block`, () => {
	test.describe( 'without experimental blocks feature flag', () => {
		test( 'can not be inserted in Post Editor when feature flag is disabled', async ( {
			editor,
			admin,
			requestUtils,
		} ) => {
			await requestUtils.setFeatureFlag( 'experimental-blocks', false );
			await admin.createNewPost();
			await expect(
				editor.insertBlock( { name: blockData.slug } )
			).rejects.toThrow(
				new RegExp(
					`Block type '${ blockData.slug }' is not registered.`
				)
			);
		} );
	} );

	test.describe( 'with experimental blocks feature flag', () => {
		test.beforeEach( async ( { admin, requestUtils } ) => {
			await requestUtils.setFeatureFlag( 'experimental-blocks', true );
			await admin.createNewPost();
		} );

		test( 'can be inserted in Post Editor and it is visible on the frontend when feature flag is enabled', async ( {
			editor,
			frontendUtils,
		} ) => {
			await editor.insertBlock( { name: blockData.slug } );
			const blockLocator = await editor.getBlockByName( blockData.slug );
			await expect(
				blockLocator.getByLabel( 'Accordion title' )
			).toHaveCount( 2 );
			await editor.publishAndVisitPost();
			const blockLocatorFrontend = await frontendUtils.getBlockByName(
				blockData.slug
			);
			await expect(
				blockLocatorFrontend.getByRole( 'button' )
			).toHaveCount( 2 );
		} );

		test( 'can add title and panel content', async ( {
			editor,
			frontendUtils,
			pageObject,
		} ) => {
			await editor.insertBlock( { name: blockData.slug } );
			await pageObject.insertAccordionGroup( [
				{
					title: 'Accordion title 1',
					content: 'Test paragraph content for first panel',
				},
				{
					title: 'Accordion title 2',
					content: 'Test paragraph content for second panel',
				},
			] );
			await editor.publishAndVisitPost();
			const blockLocatorFrontend = await frontendUtils.getBlockByName(
				blockData.slug
			);
			await expect(
				blockLocatorFrontend.getByText( 'Accordion title 1' )
			).toBeVisible();
			await expect(
				blockLocatorFrontend.getByText( 'Accordion title 2' )
			).toBeVisible();
			await expect(
				blockLocatorFrontend.getByText(
					'Test paragraph content for first panel'
				)
			).toBeAttached();
			await expect(
				blockLocatorFrontend.getByText(
					'Test paragraph content for second panel'
				)
			).toBeAttached();
		} );

		test( 'can toggle panel visibility', async ( {
			editor,
			frontendUtils,
			pageObject,
		} ) => {
			await pageObject.insertAccordionGroup( [
				{
					title: 'Accordion title',
					content: 'Test paragraph content for first panel',
				},
				{
					title: 'Accordion title 2',
					content: 'Test paragraph content for second panel',
				},
			] );
			await editor.publishAndVisitPost();
			const blockLocatorFrontend = await frontendUtils.getBlockByName(
				blockData.slug
			);
			await expect(
				blockLocatorFrontend.getByText(
					'Test paragraph content for first panel'
				)
			).not.toBeInViewport();
			await blockLocatorFrontend.getByRole( 'button' ).first().click();
			await expect(
				blockLocatorFrontend.getByText(
					'Test paragraph content for first panel'
				)
			).toBeInViewport();
		} );

		test( 'can set panel to open by default and should close when clicked', async ( {
			editor,
			frontendUtils,
			pageObject,
		} ) => {
			await pageObject.insertAccordionGroup( [
				{
					title: 'Accordion title',
					content: 'Test paragraph content 1',
				},
				{
					title: 'Accordion title 2',
					content: 'Test paragraph content 2',
				},
			] );
			const accordionPanel = await editor.getBlockByName(
				'woocommerce/accordion-item'
			);
			await editor.selectBlocks( accordionPanel.first() );

			// Open block settings sidebar and check "Open by default" option
			await editor.openDocumentSettingsSidebar();
			await editor.page
				.getByLabel( 'Settings' )
				.getByRole( 'checkbox', { name: 'Open by default' } )
				.check();

			// Publish and visit post and check that the panel is hidden.
			await editor.publishAndVisitPost();
			const blockLocatorFrontend = await frontendUtils.getBlockByName(
				blockData.slug
			);
			await expect(
				blockLocatorFrontend.getByText( 'Test paragraph content 1' )
			).toBeInViewport();
			await blockLocatorFrontend.getByRole( 'button' ).first().click();
			await expect(
				blockLocatorFrontend.getByText( 'Test paragraph content 1' )
			).not.toBeInViewport();
		} );

		test( 'can set to auto close when another panel is clicked', async ( {
			editor,
			frontendUtils,
			pageObject,
		} ) => {
			await pageObject.insertAccordionGroup( [
				{
					title: 'Accordion title 1',
					content: 'Test paragraph content 1',
				},
				{
					title: 'Accordion title 2',
					content: 'Test paragraph content 2',
				},
				{
					title: 'Accordion title 3',
					content: 'Test paragraph content 3',
				},
			] );
			const accordionPanel = await editor.getBlockByName(
				'woocommerce/accordion-group'
			);
			await editor.selectBlocks( accordionPanel.first() );

			// Open block settings sidebar and check "Open by default" option
			await editor.openDocumentSettingsSidebar();
			await editor.page
				.getByLabel( 'Settings' )
				.getByRole( 'checkbox', {
					name: 'Auto-close',
				} )
				.check();

			// Publish and visit post and check that the panel is hidden.
			await editor.publishAndVisitPost();
			const blockLocatorFrontend = await frontendUtils.getBlockByName(
				blockData.slug
			);
			await blockLocatorFrontend.getByRole( 'button' ).first().click();
			await expect(
				blockLocatorFrontend.getByText( 'Test paragraph content 1' )
			).toBeInViewport();
			await blockLocatorFrontend.getByRole( 'button' ).nth( 1 ).click();
			await expect(
				blockLocatorFrontend.getByText( 'Test paragraph content 1' )
			).not.toBeInViewport();
			await blockLocatorFrontend.getByRole( 'button' ).nth( 2 ).click();
			await expect(
				blockLocatorFrontend.getByText( 'Test paragraph content 2' )
			).not.toBeInViewport();
		} );
	} );
} );
