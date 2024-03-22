/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';
import path from 'path';

const TEMPLATE_PATH = path.join( __dirname, './price-filter.handlebars' );

const test = base.extend< {
	defaultBlockPost: Post;
} >( {
	defaultBlockPost: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Price Filter Block' },
			TEMPLATE_PATH,
			{}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},
} );

test.describe( 'Product Filter: Price Filter Block', async () => {
	test.describe( 'frontend', () => {
		test( 'With price filters applied it shows the correct price', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto(
				`${ defaultBlockPost.link }?min_price=20&max_price=67`
			);

			// Min price input field
			const leftInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-left-input'
			);
			const minPriceInput = leftInputContainer.locator( '.min' );
			const minPriceInputValue = await minPriceInput.inputValue();

			expect( minPriceInputValue ).toBe( '$20' );

			// Min price slider thumb
			const priceSlider = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-price-range-slider'
			);
			const minPriceThumb = priceSlider.locator( 'input[name="min"]' );
			const minPriceThumbValue = await minPriceThumb.inputValue();

			expect( minPriceThumbValue ).toBe( '20' );

			// Max price input field
			const rightInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-right-input'
			);
			const maxPriceInput = rightInputContainer.locator( '.max' );
			const maxPriceInputValue = await maxPriceInput.inputValue();

			expect( maxPriceInputValue ).toBe( '$67' );

			// Max price slider thumb
			const maxPriceThumb = priceSlider.locator( 'input[name="max"]' );
			const maxPriceThumbValue = await maxPriceThumb.inputValue();

			expect( maxPriceThumbValue ).toBe( '67' );
		} );

		test( 'Changes in the price input field triggers price slider updates', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto(
				`${ defaultBlockPost.link }?min_price=20&max_price=67`
			);

			// Min price input field
			const leftInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-left-input'
			);
			const minPriceInput = leftInputContainer.locator( '.min' );
			await minPriceInput.fill( '30' );
			await minPriceInput.blur();
			const minPriceInputValue = await minPriceInput.inputValue();

			expect( minPriceInputValue ).toBe( '$30' );

			// Min price slider thumb
			const priceSlider = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-price-range-slider'
			);
			const minPriceThumb = priceSlider.locator( 'input[name="min"]' );
			const minPriceThumbValue = await minPriceThumb.inputValue();

			expect( minPriceThumbValue ).toBe( '30' );

			// Max price input field
			const rightInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-right-input'
			);
			const maxPriceInput = rightInputContainer.locator( '.max' );
			await maxPriceInput.fill( '80' );
			await maxPriceInput.blur();
			const maxPriceInputValue = await maxPriceInput.inputValue();

			expect( maxPriceInputValue ).toBe( '$80' );

			// Max price slider thumb
			const maxPriceThumb = priceSlider.locator( 'input[name="max"]' );
			const maxPriceThumbValue = await maxPriceThumb.inputValue();

			expect( maxPriceThumbValue ).toBe( '80' );
		} );

		test( 'Price input field rejects min price higher than max price', async ( {
			page,
			defaultBlockPost,
		} ) => {
			const maxPrice = 67;
			await page.goto(
				`${ defaultBlockPost.link }?min_price=20&max_price=${ maxPrice }`
			);

			// Min price input field
			const leftInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-left-input'
			);
			const minPriceInput = leftInputContainer.locator( '.min' );
			await minPriceInput.fill( '80' );
			await minPriceInput.blur();
			const minPriceInputValue = await minPriceInput.inputValue();

			expect( minPriceInputValue ).toBe( '$67' );

			// Min price slider thumb
			const priceSlider = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-price-range-slider'
			);
			const minPriceThumb = priceSlider.locator( 'input[name="min"]' );
			const minPriceThumbValue = await minPriceThumb.inputValue();

			expect( minPriceThumbValue ).toBe( '67' );
		} );

		test( 'Price input field rejects max price lower than min price', async ( {
			page,
			defaultBlockPost,
		} ) => {
			const minPrice = 20;
			await page.goto(
				`${ defaultBlockPost.link }?min_price=${ minPrice }&max_price=67`
			);

			// Max price input field
			const rightInputContainer = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-right-input'
			);
			const maxPriceInput = rightInputContainer.locator( '.max' );
			await maxPriceInput.fill( '10' );
			await maxPriceInput.blur();
			const maxPriceInputValue = await maxPriceInput.inputValue();

			expect( maxPriceInputValue ).toBe( '$20' );

			// Max price slider thumb
			const priceSlider = page.locator(
				'.wp-block-woocommerce-product-filter-price-content-price-range-slider'
			);
			const maxPriceThumb = priceSlider.locator( 'input[name="max"]' );
			const maxPriceThumbValue = await maxPriceThumb.inputValue();

			expect( maxPriceThumbValue ).toBe( '20' );
		} );
	} );
} );
