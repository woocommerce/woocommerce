/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_price-filter'
		);
		await use( compiler );
	},
} );

test.describe( 'Product Filter: Price Filter Block', () => {
	test.describe( 'frontend', () => {
		test.beforeEach( async ( { requestUtils, templateCompiler } ) => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-enable-experimental-features'
			);
			await templateCompiler.compile();
		} );

		test( 'clear button is not shown on initial page load', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'With price filters applied it shows the correct price', async ( {
			page,
		} ) => {
			await page.goto( '/shop?min_price=20&max_price=67' );

			// Min price input field
			const leftInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-left-input'
			);
			const minPriceInput = leftInputContainer.locator( '.min' );

			await expect( minPriceInput ).toHaveValue( '$20' );

			// Min price slider thumb
			const priceSlider = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-price-range-slider'
			);
			const minPriceThumb = priceSlider.locator( 'input[name="min"]' );

			await expect( minPriceThumb ).toHaveValue( '20' );

			// Max price input field
			const rightInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-right-input'
			);
			const maxPriceInput = rightInputContainer.locator( '.max' );

			await expect( maxPriceInput ).toHaveValue( '$67' );

			// Max price slider thumb
			const maxPriceThumb = priceSlider.locator( 'input[name="max"]' );

			await expect( maxPriceThumb ).toHaveValue( '67' );
		} );

		test( 'clear button appears after a filter is applied', async ( {
			page,
		} ) => {
			await page.goto( '/shop?min_price=20&max_price=67' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop?min_price=20&max_price=67' );

			const defaultRange = ( await page
				.locator( '.wp-block-woocommerce-product-filter-price' )
				.getAttribute( 'data-wc-context' ) ) as string;
			const defaultMinRange = JSON.parse( defaultRange ).minRange;
			const defaultMaxRange = JSON.parse( defaultRange ).maxRange;

			// Min price input field
			const leftInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-left-input'
			);
			const minPriceInput = leftInputContainer.locator( '.min' );
			await minPriceInput.fill( String( defaultMinRange ) );

			await page.waitForURL(
				( url ) => ! url.href.includes( 'min_price' )
			);

			// Max price input field
			const rightInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-right-input'
			);
			const maxPriceInput = rightInputContainer.locator( '.max' );
			await maxPriceInput.fill( String( defaultMaxRange ) );

			await page.waitForURL(
				( url ) => ! url.href.includes( 'max_price' )
			);

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
		} ) => {
			await page.goto( '/shop?min_price=20&max_price=67' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			await page.waitForURL( '/shop/' );

			const defaultRangePrice = ( await page
				.locator( '.wp-block-woocommerce-product-filter-price' )
				.getAttribute( 'data-wc-context' ) ) as string;

			const defaultMinRange = JSON.parse( defaultRangePrice ).minRange;
			const defaultMaxRange = JSON.parse( defaultRangePrice ).maxRange;
			const defaultMinPrice = JSON.parse( defaultRangePrice ).minPrice;
			const defaultMaxPrice = JSON.parse( defaultRangePrice ).maxPrice;

			expect( defaultMinRange ).toEqual( defaultMinPrice );
			expect( defaultMaxRange ).toEqual( defaultMaxPrice );
		} );

		test( 'Changes in the price input field triggers price slider updates', async ( {
			page,
		} ) => {
			await page.goto( '/shop?min_price=20&max_price=67' );

			// Min price input field
			const leftInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-left-input'
			);
			const minPriceInput = leftInputContainer.locator( '.min' );
			await minPriceInput.fill( '30' );
			await minPriceInput.blur();

			await expect( minPriceInput ).toHaveValue( '$30' );

			// Min price slider thumb
			const priceSlider = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-price-range-slider'
			);
			const minPriceThumb = priceSlider.locator( 'input[name="min"]' );

			await expect( minPriceThumb ).toHaveValue( '30' );

			// Max price input field
			const rightInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-right-input'
			);
			const maxPriceInput = rightInputContainer.locator( '.max' );
			await maxPriceInput.fill( '80' );
			await maxPriceInput.blur();

			await expect( maxPriceInput ).toHaveValue( '$80' );

			// Max price slider thumb
			const maxPriceThumb = priceSlider.locator( 'input[name="max"]' );

			await expect( maxPriceThumb ).toHaveValue( '80' );
		} );

		test( 'Price input field rejects min price higher than max price', async ( {
			page,
		} ) => {
			await page.goto( '/shop?min_price=20&max_price=67' );

			// Min price input field
			const minPriceInput = page
				.getByRole( 'textbox' )
				.and( page.locator( '[name="min"]' ) );
			await minPriceInput.fill( '80' );
			await minPriceInput.blur();

			await expect( minPriceInput ).toHaveValue( '$67' );

			// Min price slider thumb
			const minPriceThumb = page
				.getByRole( 'slider' )
				.and( page.locator( '[name="min"]' ) );

			await expect( minPriceThumb ).toHaveValue( '67' );
		} );

		test( 'Price input field rejects max price lower than min price', async ( {
			page,
		} ) => {
			await page.goto( '/shop?min_price=20&max_price=67' );

			// Max price input field
			const maxPriceInput = page
				.getByRole( 'textbox' )
				.and( page.locator( '[name="max"]' ) );
			await maxPriceInput.fill( '10' );
			await maxPriceInput.blur();

			await expect( maxPriceInput ).toHaveValue( '$20' );

			// Max price slider thumb
			const maxPriceThumb = page
				.getByRole( 'slider' )
				.and( page.locator( '[name="max"]' ) );

			await expect( maxPriceThumb ).toHaveValue( '20' );
		} );
	} );
} );
