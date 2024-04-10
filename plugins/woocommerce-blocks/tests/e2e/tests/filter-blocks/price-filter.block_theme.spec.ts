/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import { ExtendedTemplate } from '../../types/e2e-test-utils-playwright';

const TEMPLATE_PATH = path.join( __dirname, './price-filter.handlebars' );

const test = base.extend< {
	defaultBlockTemplate: ExtendedTemplate;
} >( {
	defaultBlockTemplate: async ( { requestUtils, templateApiUtils }, use ) => {
		const testingTemplate = await requestUtils.updateProductCatalogContent(
			TEMPLATE_PATH,
			{
				attributes: {
					attributeId: 1,
				},
			}
		);
		await use( testingTemplate );
		await templateApiUtils.revertTemplate( testingTemplate.id );
	},
} );

test.describe( 'Product Filter: Price Filter Block', async () => {
	test.describe( 'frontend', () => {
		test( 'With price filters applied it shows the correct price', async ( {
			page,
			defaultBlockTemplate,
		} ) => {
			await page.goto(
				`${ defaultBlockTemplate.link }?min_price=20&max_price=67`
			);

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

		test( 'Changes in the price input field triggers price slider updates', async ( {
			page,
			defaultBlockTemplate,
		} ) => {
			await page.goto(
				`${ defaultBlockTemplate.link }?min_price=20&max_price=67`
			);

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
			defaultBlockTemplate,
		} ) => {
			await page.goto(
				`${ defaultBlockTemplate.link }?min_price=20&max_price=67`
			);

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
			defaultBlockTemplate,
		} ) => {
			await page.goto(
				`${ defaultBlockTemplate.link }?min_price=20&max_price=67`
			);

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
