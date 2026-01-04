/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';
import { ProductGalleryPage } from '../product-gallery/product-gallery.page';

const test = base.extend< {
	pageObject: AddToCartWithOptionsPage;
	productGalleryPageObject: ProductGalleryPage;
} >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
	productGalleryPageObject: async (
		{ page, editor, frontendUtils },
		use
	) => {
		const pageObject = new ProductGalleryPage( {
			page,
			editor,
			frontendUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Add to Cart + Options Block - Lazy Loading Variations', () => {
	// Activate the plugin that lowers the threshold to 3, so Hoodie (~6 variations)
	// will trigger lazy loading mode.
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-lazy-load-variations'
		);
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deactivatePlugin(
			'woocommerce-blocks-test-lazy-load-variations'
		);
	} );

	test( 'fetches variation data via AJAX when threshold is exceeded', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		// Track requests to the Store API products endpoint.
		const variationRequests: string[] = [];
		page.on( 'request', ( request ) => {
			const url = request.url();
			// Match requests like /wc/store/v1/products/123 (variation fetches)
			if ( /\/wc\/store\/v1\/products\/\d+$/.test( url ) ) {
				variationRequests.push( url );
			}
		} );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		// Before selecting a variation, no AJAX requests should have been made.
		expect( variationRequests.length ).toBe( 0 );

		// Select a variation.
		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const logoNoOption = page.locator( 'label:has-text("No")' );

		await colorBlueOption.click();
		await logoNoOption.click();

		// Wait for the variation data to be fetched and UI to update.
		await expect( async () => {
			// At least one AJAX request should have been made to fetch variation data.
			expect( variationRequests.length ).toBeGreaterThan( 0 );
		} ).toPass( { timeout: 5000 } );

		// Verify the UI updates correctly with the fetched data.
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();
		await expect( productPrice ).toHaveText( '$45.00' );
	} );

	test( 'deduplicates concurrent requests for the same variation', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		// Track requests to the Store API products endpoint.
		const variationRequests: string[] = [];
		page.on( 'request', ( request ) => {
			const url = request.url();
			if ( /\/wc\/store\/v1\/products\/\d+$/.test( url ) ) {
				variationRequests.push( url );
			}
		} );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		// Select a variation - multiple blocks will try to fetch the same data.
		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const logoNoOption = page.locator( 'label:has-text("No")' );

		await colorBlueOption.click();
		await logoNoOption.click();

		// Wait for UI to update.
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();
		await expect( productPrice ).toHaveText( '$45.00' );

		// Count unique variation IDs requested.
		const variationIds = variationRequests.map( ( url ) => {
			const match = url.match( /\/products\/(\d+)$/ );
			return match ? match[ 1 ] : null;
		} );

		// Group by variation ID and count.
		const requestCounts: Record< string, number > = {};
		for ( const id of variationIds ) {
			if ( id ) {
				requestCounts[ id ] = ( requestCounts[ id ] || 0 ) + 1;
			}
		}

		// Each variation should only be fetched once (deduplication working).
		for ( const [ variationId, count ] of Object.entries( requestCounts ) ) {
			expect(
				count,
				`Variation ${ variationId } should only be fetched once`
			).toBe( 1 );
		}
	} );

	test( 'updates sibling blocks correctly when variation is selected in lazy mode', async ( {
		page,
		pageObject,
		productGalleryPageObject,
		editor,
	} ) => {
		// Set up test data: make one variation out of stock with specific attributes.
		const variationDescription =
			'This is the variation description for lazy load test';
		let cliOutput = await wpCLI(
			`post list --post_type=product --field=ID --name="Hoodie" --format=ids`
		);
		const hoodieProductId = cliOutput.stdout.match( /\d+/g )?.pop();
		cliOutput = await wpCLI(
			'post list --post_type=product_variation --field=ID --name="Hoodie - Blue, No" --format=ids'
		);
		const hoodieProductVariationId = cliOutput.stdout
			.match( /\d+/g )
			?.pop();
		await wpCLI(
			`wc product update ${ hoodieProductId } --manage_stock=true --stock_quantity=100 --user=1`
		);
		await wpCLI(
			`wc product_variation update ${ hoodieProductId } ${ hoodieProductVariationId } --manage_stock=true --in_stock=false --weight=2 --description="${ variationDescription }" --user=1`
		);

		await pageObject.updateSingleProductTemplate();

		// Transform to Product Gallery block to test image switching.
		const productImageGalleryBlock = await editor.getBlockByName(
			'woocommerce/product-image-gallery'
		);
		await editor.selectBlocks( productImageGalleryBlock );
		await editor.transformBlockTo( 'woocommerce/product-gallery' );

		// Insert Product Details block to test weight updates.
		await editor.insertBlock( {
			name: 'woocommerce/product-details',
		} );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		// Open additional information accordion.
		await page
			.getByRole( 'button', { name: 'Additional Information' } )
			.click();

		// Verify initial state (parent product data).
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();
		await expect( productPrice ).toHaveText( /\$42.00 – \$45.00.*/ );
		await expect( page.getByText( '100 in stock' ) ).toBeVisible();
		await expect( page.getByText( 'SKU: woo-hoodie' ) ).toBeVisible();

		// Select the out-of-stock variation.
		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const logoNoOption = page.locator( 'label:has-text("No")' );

		await colorBlueOption.click();
		await logoNoOption.click();

		// Verify all sibling blocks update with lazy-loaded data.
		await expect( productPrice ).toHaveText( '$45.00' );
		await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
		await expect( page.getByText( 'SKU: woo-hoodie-blue' ) ).toBeVisible();
		await expect(
			page
				.getByLabel( 'Additional Information', { exact: true } )
				.getByText( '2 lbs' )
		).toBeVisible();
		await expect( page.getByText( variationDescription ) ).toBeVisible();

		// Verify gallery image switches.
		await expect( async () => {
			const viewerImageId =
				await productGalleryPageObject.getViewerImageId();
			expect( viewerImageId ).toBe( '35' );
		} ).toPass( { timeout: 1000 } );
	} );

	test( 'caches fetched variation data for reuse', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		// Track requests.
		const variationRequests: string[] = [];
		page.on( 'request', ( request ) => {
			const url = request.url();
			if ( /\/wc\/store\/v1\/products\/\d+$/.test( url ) ) {
				variationRequests.push( url );
			}
		} );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const colorRedOption = page.locator( 'label:has-text("Red")' );
		const logoNoOption = page.locator( 'label:has-text("No")' );

		// Select first variation.
		await colorBlueOption.click();
		await logoNoOption.click();

		// Wait for data to load.
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();
		await expect( productPrice ).toHaveText( '$45.00' );

		const requestsAfterFirstSelection = variationRequests.length;

		// Switch to a different variation.
		await colorRedOption.click();
		await expect( productPrice ).toHaveText( '$42.00' );

		const requestsAfterSecondSelection = variationRequests.length;

		// Switch back to the first variation.
		await colorBlueOption.click();
		await expect( productPrice ).toHaveText( '$45.00' );

		const requestsAfterThirdSelection = variationRequests.length;

		// The third selection (switching back) should NOT trigger a new request
		// because the data should be cached.
		expect( requestsAfterThirdSelection ).toBe(
			requestsAfterSecondSelection
		);

		// But switching to the second variation should have triggered a request.
		expect( requestsAfterSecondSelection ).toBeGreaterThan(
			requestsAfterFirstSelection
		);
	} );

	test( 'allows adding variable products to cart in lazy mode', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		// Select a variation.
		const colorGreenOption = page.locator( 'label:has-text("Green")' );
		const logoNoOption = page.locator( 'label:has-text("No")' );

		await colorGreenOption.click();
		await logoNoOption.click();

		// Wait for variation data to load.
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();
		await expect( productPrice ).toHaveText( '$45.00' );

		// Add to cart.
		const addToCartButton = page
			.locator( '.wp-block-add-to-cart-with-options' )
			.getByRole( 'button', { name: 'Add to cart' } );

		await addToCartButton.click();

		// Verify success.
		await expect( page.getByText( '1 in cart' ) ).toBeVisible();
	} );
} );