/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await use( compiler );
	},
} );

test.describe( 'woocommerce/product-filters - Frontend', () => {
	test( 'preserves saved local categories in frontend filter counts', async ( {
		page,
		requestUtils,
	} ) => {
		const parentCategory = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products/categories',
			data: { name: 'Collection parent' },
		} );
		const childCategory = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products/categories',
			data: { name: 'Collection child', parent: parentCategory.id },
		} );
		const outsideCategory = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products/categories',
			data: { name: 'Outside collection' },
		} );

		for ( const fixture of [
			{
				name: 'Direct parent product',
				category: parentCategory.id,
				price: '17',
				rating: 2,
			},
			{
				name: 'Child-only product',
				category: childCategory.id,
				price: '93',
				rating: 5,
			},
			{
				name: 'Outside product',
				category: outsideCategory.id,
				price: '93',
				rating: 4,
			},
		] ) {
			const product = await requestUtils.rest( {
				method: 'POST',
				path: '/wc/v3/products',
				data: {
					name: fixture.name,
					type: 'simple',
					status: 'publish',
					regular_price: fixture.price,
					categories: [ { id: fixture.category } ],
				},
			} );
			await requestUtils.rest( {
				method: 'POST',
				path: '/wc/v3/products/reviews',
				data: {
					product_id: product.id,
					review: `Review for ${ fixture.name }`,
					reviewer: 'Test shopper',
					reviewer_email: 'shopper@example.com',
					rating: fixture.rating,
					status: 'approved',
				},
			} );
		}

		const collectionAttributes = JSON.stringify( {
			queryId: 1,
			query: {
				perPage: 9,
				pages: 0,
				offset: 0,
				postType: 'product',
				order: 'asc',
				orderBy: 'title',
				inherit: false,
				filterable: true,
				isProductCollectionBlock: true,
				taxQuery: { product_cat: [ parentCategory.id ] },
			},
			displayLayout: { type: 'flex', columns: 3 },
		} );
		const post = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				title: 'Local collection filter counts',
				status: 'publish',
				content: `<!-- wp:woocommerce/product-collection ${ collectionAttributes } -->
<div class="wp-block-woocommerce-product-collection">
	<!-- wp:woocommerce/product-filters {"overlayMode":"off"} -->
	<div class="wp-block-woocommerce-product-filters wc-block-product-filters">
		<!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_cat","showCounts":true,"hideEmpty":true} -->
		<div class="wp-block-woocommerce-product-filter-taxonomy">
			<!-- wp:woocommerce/product-filter-checkbox-list -->
			<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
			<!-- /wp:woocommerce/product-filter-checkbox-list -->
		</div>
		<!-- /wp:woocommerce/product-filter-taxonomy -->
		<!-- wp:woocommerce/product-filter-rating {"showCounts":true} -->
		<div class="wp-block-woocommerce-product-filter-rating">
			<!-- wp:woocommerce/product-filter-checkbox-list -->
			<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
			<!-- /wp:woocommerce/product-filter-checkbox-list -->
		</div>
		<!-- /wp:woocommerce/product-filter-rating -->
	</div>
	<!-- /wp:woocommerce/product-filters -->
	<!-- wp:woocommerce/product-template -->
		<!-- wp:post-title {"level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
	<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->`,
			},
		} );

		await page.goto( post.link );

		const collection = page.locator(
			'.wp-block-woocommerce-product-collection'
		);
		const productTitles = collection.locator( '.wp-block-post-title' );
		const categoryFilter = collection.locator(
			'.wp-block-woocommerce-product-filter-taxonomy'
		);
		const parentCheckbox = categoryFilter.getByRole( 'checkbox', {
			name: /Collection parent/,
		} );
		const ratingFilter = collection.locator(
			'.wp-block-woocommerce-product-filter-rating'
		);

		await expect( productTitles ).toHaveText( [ 'Direct parent product' ] );
		await expect( parentCheckbox ).toBeVisible();
		await expect( categoryFilter.getByRole( 'checkbox' ) ).toHaveCount( 1 );
		await expect(
			categoryFilter.getByText( 'Collection child', { exact: true } )
		).toHaveCount( 0 );
		await expect(
			categoryFilter.getByText( 'Outside collection', { exact: true } )
		).toHaveCount( 0 );
		await expect(
			categoryFilter.locator(
				'.wc-block-product-filter-checkbox-list__count'
			)
		).toHaveText( '(1)' );
		await expect(
			ratingFilter.getByRole( 'checkbox', { name: 'Rated 2 out of 5' } )
		).toBeVisible();
		await expect( ratingFilter.getByRole( 'checkbox' ) ).toHaveCount( 1 );

		await parentCheckbox.check();
		await page.waitForURL(
			( url ) =>
				url.searchParams.get( 'categories' ) === parentCategory.slug
		);

		await expect( parentCheckbox ).toBeChecked();
		await expect( productTitles ).toHaveText( [ 'Direct parent product' ] );
		await expect( categoryFilter.getByRole( 'checkbox' ) ).toHaveCount( 1 );
		await expect(
			categoryFilter.locator(
				'.wc-block-product-filter-checkbox-list__count'
			)
		).toHaveText( '(1)' );
		await expect(
			ratingFilter.getByRole( 'checkbox', { name: 'Rated 2 out of 5' } )
		).toBeVisible();
		await expect( ratingFilter.getByRole( 'checkbox' ) ).toHaveCount( 1 );
	} );

	test.describe( 'Overlay', () => {
		test.beforeEach( async ( { templateCompiler, page } ) => {
			await templateCompiler.compile( {
				attributes: {
					attributeId: 1,
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
			const overlay = page.getByRole( 'dialog' );

			await expect( openOverlayButton ).toBeVisible();
			await expect( overlay ).not.toBeInViewport();

			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();

			// Close overlay by clicking close button.
			const closeOverlayButton = page.getByRole( 'button', {
				name: 'Close',
			} );

			await expect( closeOverlayButton ).toBeVisible();

			await closeOverlayButton.click();

			await expect( overlay ).not.toBeInViewport();

			// Close overlay by hitting Esc.
			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();

			await page.keyboard.press( 'Escape' );

			await expect( overlay ).not.toBeInViewport();

			// Close overlay by clicking Apply button.
			await openOverlayButton.click();

			await expect( overlay ).toBeInViewport();

			const applyButton = page.getByRole( 'button', {
				name: 'Apply',
			} );

			await expect( applyButton ).toBeVisible();

			await applyButton.click();

			await expect( overlay ).not.toBeInViewport();
		} );

		// Skipping these tests until we can move this block to @wordpress/interactivity.
		// eslint-disable-next-line playwright/no-skipped-test
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

			await templateCompiler.compile( {
				attributes: {
					attributeId: 1,
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
