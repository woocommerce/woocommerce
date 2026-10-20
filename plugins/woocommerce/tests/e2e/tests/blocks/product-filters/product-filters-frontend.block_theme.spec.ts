/**
 * External dependencies
 */
import {
	TemplateCompiler,
	getProductAttributeIds,
	test as base,
	expect,
} from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, provideTemplate ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await provideTemplate( compiler );
	},
} );

test.describe( 'woocommerce/product-filters - Frontend', () => {
	test.describe( 'Overlay', () => {
		test.beforeEach( async ( { templateCompiler, page } ) => {
			const { colorAttributeId } = await getProductAttributeIds();
			await templateCompiler.compile( {
				attributes: {
					attributeId: colorAttributeId,
				},
			} );

			await page.addInitScript( () => {
				// Mock the wc global variable.
				if ( typeof window.wc === 'undefined' ) {
					window.wc = {
						wcSettings: {
							getSetting() {
								return true;
							},
						},
					};
				}
			} );
		} );

		test( 'On mobile, overlay can be open and close.', async ( {
			page,
		} ) => {
			await page.setViewportSize( { width: 400, height: 600 } );
			await page.goto( '/shop' );

			const openOverlayButton = page.getByRole( 'button', {
				name: 'Filter products',
			} );
			const overlay = page.getByRole( 'dialog', {
				name: 'Product Filters',
			} );

			await expect( openOverlayButton ).toBeVisible();
			await expect( overlay ).toHaveCount( 0 );

			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();

			// Close overlay by clicking close button.
			const closeOverlayButton = page.getByRole( 'button', {
				name: 'Close',
			} );

			await expect( closeOverlayButton ).toBeFocused();

			await closeOverlayButton.click();

			await expect( openOverlayButton ).toBeFocused();
			await expect( overlay ).toHaveCount( 0 );

			// Close overlay by hitting Esc.
			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();
			await expect( closeOverlayButton ).toBeFocused();

			await page.keyboard.press( 'Escape' );

			await expect( openOverlayButton ).toBeFocused();
			await expect( overlay ).toHaveCount( 0 );

			// Close overlay by clicking Apply button.
			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();
			await expect( closeOverlayButton ).toBeFocused();

			const applyButton = page.getByRole( 'button', {
				name: 'Apply',
			} );

			await applyButton.focus();
			await page.keyboard.press( 'Tab' );
			await expect( closeOverlayButton ).toBeFocused();

			await applyButton.click();

			await expect( openOverlayButton ).toBeFocused();
			await expect( overlay ).toHaveCount( 0 );

			await openOverlayButton.click();
			await expect( closeOverlayButton ).toBeFocused();
			const lastFilter = overlay.getByRole( 'checkbox' ).last();
			await lastFilter.focus();

			await page.setViewportSize( { width: 1280, height: 800 } );
			await expect( openOverlayButton ).toBeHidden();
			await expect( overlay ).not.toHaveAttribute( 'aria-modal', 'true' );
			await expect( lastFilter ).toBeFocused();
			await page.keyboard.press( 'Tab' );
			await expect
				.poll( () =>
					overlay.evaluate( ( element ) =>
						element.contains( element.ownerDocument.activeElement )
					)
				)
				.toBe( false );

			await page.setViewportSize( { width: 400, height: 600 } );
			await expect( overlay ).toHaveCount( 0 );
			await openOverlayButton.click();
			await expect( closeOverlayButton ).toBeFocused();
			await page.keyboard.press( 'Escape' );
			await expect( openOverlayButton ).toBeFocused();
		} );

		// Skipping these tests until we can move this block to @wordpress/interactivity.

		test.skip( 'filter is working inside overlay', async ( { page } ) => {
			await page.setViewportSize( { width: 400, height: 600 } );
			await page.goto( '/shop' );

			await page
				.getByRole( 'button', {
					name: 'Filter products',
				} )
				.click();

			await page.getByText( 'Gray' ).click();

			await page
				.getByRole( 'button', {
					name: 'Apply',
				} )
				.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 2 );
		} );
	} );

	test.describe( 'Multiple instances', () => {
		test( 'syncs active filters between Product Filters blocks', async ( {
			page,
			requestUtils,
		} ) => {
			await page.addInitScript( () => {
				// Mock the wc global variable.
				if ( typeof window.wc === 'undefined' ) {
					window.wc = {
						wcSettings: {
							getSetting() {
								return true;
							},
						},
					};
				}
			} );

			const templateCompiler = await requestUtils.createTemplateFromFile(
				'archive-product_multiple-product-filters'
			);
			const { colorAttributeId } = await getProductAttributeIds();

			await templateCompiler.compile( {
				attributes: {
					attributeId: colorAttributeId,
				},
			} );

			await page.goto( '/shop' );

			const productFilters = page.locator(
				'.wp-block-woocommerce-product-filters'
			);
			await expect( productFilters ).toHaveCount( 2 );

			const activeFiltersBlock = productFilters.first();
			const filterOptionsBlock = productFilters.nth( 1 );
			const grayCheckbox = filterOptionsBlock.getByRole( 'checkbox', {
				name: 'Gray',
			} );
			const grayActiveFilter = activeFiltersBlock.getByText(
				'Color: Gray',
				{ exact: true }
			);

			await expect( grayActiveFilter ).toBeHidden();

			await grayCheckbox.click();
			await page.waitForURL(
				( url ) => url.searchParams.get( 'filter_color' ) === 'gray'
			);

			await expect( grayActiveFilter ).toBeVisible();
			await expect( grayCheckbox ).toBeChecked();

			await activeFiltersBlock
				.getByRole( 'button', {
					name: 'Remove filter: Color: Gray',
				} )
				.click();
			await page.waitForURL(
				( url ) => ! url.searchParams.has( 'filter_color' )
			);

			await expect( grayActiveFilter ).toBeHidden();
			await expect( grayCheckbox ).not.toBeChecked();
		} );
	} );
} );
