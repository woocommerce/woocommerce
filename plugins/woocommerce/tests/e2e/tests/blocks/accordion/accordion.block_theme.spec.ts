/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/accordion-group',
};

const accordionInnerBlocks = [
	{
		name: 'woocommerce/accordion-item',
		innerBlocks: [
			{
				name: 'woocommerce/accordion-header',
				attributes: {
					title: 'First Accordion Header',
				},
			},
			{
				name: 'woocommerce/accordion-panel',
				innerBlocks: [
					{
						name: 'core/paragraph',
						attributes: {
							content: 'First accordion content',
						},
					},
				],
			},
		],
	},
	{
		name: 'woocommerce/accordion-item',
		innerBlocks: [
			{
				name: 'woocommerce/accordion-header',
				attributes: {
					title: 'Second Accordion Header',
				},
			},
			{
				name: 'woocommerce/accordion-panel',
				innerBlocks: [
					{
						name: 'core/paragraph',
						attributes: {
							content: 'Second accordion content',
						},
					},
				],
			},
		],
	},
];

test.describe( `${ blockData.slug } Block - Deprecation`, () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'shows deprecation notice and converts all inner blocks to core accordion on upgrade (WP 6.9+)', async ( {
		editor,
		frontendUtils,
		page,
		wpCoreVersion,
	} ) => {
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip(
			wpCoreVersion < 6.9,
			'This test requires WordPress 6.9 or later'
		);

		// Insert WooCommerce accordion block with inner blocks and content.
		await editor.insertBlock( {
			name: blockData.slug,
			innerBlocks: accordionInnerBlocks,
		} );

		// Wait for the deprecation notice to appear.
		await expect(
			editor.canvas.getByText(
				'This version of the Accordion block is outdated. Upgrade to continue using.'
			)
		).toBeVisible();

		// Verify legacy block still renders as expected before upgrade. Save as draft and preview it.
		await editor.saveDraft();
		const postId = await page.evaluate( () => {
			return window.wp.data.select( 'core/editor' ).getCurrentPostId();
		} );
		await page.goto( `/?p=${ postId }&preview=true` );
		const legacyAccordionFrontend = frontendUtils.page.locator(
			'.wp-block-woocommerce-accordion-group'
		);
		await expect( legacyAccordionFrontend ).toBeVisible();

		// Verify legacy accordion has accordion items with buttons.
		const legacyAccordionButtons =
			legacyAccordionFrontend.getByRole( 'button' );
		const legacyItemCount = await legacyAccordionButtons.count();
		expect( legacyItemCount ).toBe( 2 );

		// Verify the content is visible.
		await expect(
			legacyAccordionFrontend.getByText( 'First Accordion Header' )
		).toBeVisible();
		await expect(
			legacyAccordionFrontend.getByText( 'Second Accordion Header' )
		).toBeVisible();

		// Go back to editor.
		await page.goBack();

		// Verify upgrade button is displayed.
		const upgradeButton = editor.canvas.getByRole( 'button', {
			name: 'Upgrade Block',
		} );
		await expect( upgradeButton ).toBeVisible();

		// Click the upgrade button.
		await upgradeButton.click();

		// Verify the block was converted to core/accordion.
		const coreAccordion = await editor.getBlockByName( 'core/accordion' );
		await expect( coreAccordion ).toBeVisible();

		// Verify the WooCommerce accordion block is no longer present.
		const wooAccordion = editor.canvas.locator(
			'[data-type="woocommerce/accordion-group"]'
		);
		await expect( wooAccordion ).toHaveCount( 0 );

		// Verify all inner blocks are converted correctly.
		// Check that accordion items exist (woocommerce/accordion-item → core/accordion-item).
		const coreAccordionItems = editor.canvas.locator(
			'[data-type="core/accordion-item"]'
		);
		const itemCount = await coreAccordionItems.count();
		expect( itemCount ).toBeGreaterThan( 0 );

		// Check accordion headings (woocommerce/accordion-header → core/accordion-heading).
		const coreAccordionHeadings = editor.canvas.locator(
			'[data-type="core/accordion-heading"]'
		);
		await expect( coreAccordionHeadings ).toHaveCount( itemCount );

		// Check accordion panels (woocommerce/accordion-panel → core/accordion-panel).
		const coreAccordionPanels = editor.canvas.locator(
			'[data-type="core/accordion-panel"]'
		);
		await expect( coreAccordionPanels ).toHaveCount( itemCount );

		// Verify no WooCommerce accordion inner blocks remain.
		const wooAccordionItems = editor.canvas.locator(
			'[data-type="woocommerce/accordion-item"]'
		);
		await expect( wooAccordionItems ).toHaveCount( 0 );

		const wooAccordionHeaders = editor.canvas.locator(
			'[data-type="woocommerce/accordion-header"]'
		);
		await expect( wooAccordionHeaders ).toHaveCount( 0 );

		const wooAccordionPanels = editor.canvas.locator(
			'[data-type="woocommerce/accordion-panel"]'
		);
		await expect( wooAccordionPanels ).toHaveCount( 0 );

		// Publish the post.
		await editor.publishAndVisitPost();

		// Verify the core accordion block is visible on the frontend.
		const accordionFrontend = frontendUtils.page.locator(
			'.wp-block-accordion'
		);
		await expect( accordionFrontend ).toBeVisible();

		// Verify accordion buttons are present.
		const accordionButtons = accordionFrontend.getByRole( 'button' );
		await expect( accordionButtons ).toHaveCount( itemCount );
	} );

	test( 'does not show deprecation notice in WordPress 6.8 or earlier', async ( {
		editor,
		wpCoreVersion,
	} ) => {
		// eslint-disable-next-line playwright/no-skipped-test
		test.skip(
			wpCoreVersion >= 6.9,
			'This test is only for WordPress 6.8 or earlier'
		);

		// Insert WooCommerce accordion block with inner blocks and content.
		await editor.insertBlock( {
			name: blockData.slug,
			innerBlocks: accordionInnerBlocks,
		} );

		// Verify deprecation notice is NOT shown.
		const deprecationNotice = editor.canvas.getByText(
			'This version of the Accordion block is outdated. Upgrade to continue using.'
		);
		await expect( deprecationNotice ).toBeHidden();
	} );
} );
