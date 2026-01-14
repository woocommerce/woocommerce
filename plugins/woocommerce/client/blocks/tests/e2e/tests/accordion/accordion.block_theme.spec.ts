/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/accordion-group',
};

test.describe( `${ blockData.slug } Block - Deprecation`, () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'shows deprecation notice and converts all inner blocks to core accordion on upgrade', async ( {
		editor,
		frontendUtils,
	} ) => {
		// Insert WooCommerce accordion block.
		await editor.insertBlock( { name: blockData.slug } );

		// Verify deprecation message is displayed in the canvas.
		await expect(
			editor.canvas.getByText(
				'This version of the Accordion block is outdated. Upgrade to continue using.'
			)
		).toBeVisible();

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
} );
