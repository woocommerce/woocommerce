/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_all-product-filters'
		);
		await use( compiler );
	},
} );

test.describe( 'woocommerce/product-filter-price - Frontend', () => {
	test.beforeEach( async ( { templateCompiler, page } ) => {
		await templateCompiler.compile();

		await page.addInitScript( () => {
			// Mock the wc global variable.
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			if ( typeof ( window as any ).wc === 'undefined' ) {
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				( window as any ).wc = {
					wcSettings: {
						getSetting() {
							return true;
						},
					},
				};
			}
		} );
	} );

	test( 'price range should be always updated to latest server value', async ( {
		page,
	} ) => {
		await page.goto( '/shop' );

		// Get initial price range constraints from the min range input
			const minRangeInput = page.getByLabel(
				'Filter products by minimum price'
			).first();
			const maxRangeInput = page.getByLabel(
				'Filter products by maximum price'
			).first();

		// Get initial min and max range values
		const initialMinRange = await minRangeInput.getAttribute( 'min' );
		const initialMaxRange = await maxRangeInput.getAttribute( 'max' );

		expect( initialMinRange ).toBeTruthy();
		expect( initialMaxRange ).toBeTruthy();

		const initialMinRangeNum = parseFloat( initialMinRange || '0' );
		const initialMaxRangeNum = parseFloat( initialMaxRange || '0' );

		// Apply a color filter (Blue) which should narrow the price range
		const blueCheckbox = page.getByText( 'Blue' );
		await blueCheckbox.click();

		// Wait for navigation
		await page.waitForURL( /.*filter_color=blue.*/ );

		// Wait for the price range inputs to update their min/max attributes
		await expect( minRangeInput ).toHaveAttribute( 'min', {
			timeout: 2000,
		} );
		await expect( maxRangeInput ).toHaveAttribute( 'max', {
			timeout: 2000,
		} );

		// Get updated min and max range values
		const updatedMinRange = await minRangeInput.getAttribute( 'min' );
		const updatedMaxRange = await maxRangeInput.getAttribute( 'max' );

		expect( updatedMinRange ).toBeTruthy();
		expect( updatedMaxRange ).toBeTruthy();

		const updatedMinRangeNum = parseFloat( updatedMinRange || '0' );
		const updatedMaxRangeNum = parseFloat( updatedMaxRange || '0' );

		// Verify the price range has changed and is narrower
		// The range should be narrower (max should be <= initial max, min should be >= initial min)
		// and at least one boundary should have changed
		expect( updatedMaxRangeNum ).toBeLessThanOrEqual( initialMaxRangeNum );
		expect( updatedMinRangeNum ).toBeGreaterThanOrEqual(
			initialMinRangeNum
		);

		// At least one boundary should have changed (range should be narrower)
		const maxChanged = updatedMaxRangeNum < initialMaxRangeNum;
		const minChanged = updatedMinRangeNum > initialMinRangeNum;

		expect( maxChanged || minChanged ).toBe( true );
	} );
} );
