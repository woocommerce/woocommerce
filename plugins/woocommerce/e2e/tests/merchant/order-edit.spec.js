const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const uuid = require( 'uuid' );

let orderId;

test.describe( 'WooCommerce Orders > Edit order', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'orders', {
				status: 'processing',
			} )
			.then( ( response ) => {
				orderId = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `orders/${ orderId }`, { force: true } );
	} );

	test( 'can view single order', async ( { page } ) => {
		// go to orders page
		await page.goto( 'wp-admin/edit.php?post_type=shop_order' );

		// confirm we're on the orders page
		await expect( page.locator( 'h1.components-text' ) ).toContainText(
			'Orders'
		);

		// open order we created
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );

		// make sure we're on the order details page
		await expect( page.locator( 'h1.components-text' ) ).toContainText(
			'Edit Order'
		);
	} );

	test( 'can update order status', async ( { page } ) => {
		// open order we created
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );

		// update order status to Completed
		await page.selectOption( '#order_status', 'wc-completed' );
		await page.click( 'button.save_order' );

		// verify order status changed and note added
		await expect( page.locator( '#order_status' ) ).toHaveValue(
			'wc-completed'
		);
		await expect(
			page.locator( '#woocommerce-order-notes .note_content >> nth=0' )
		).toContainText( 'Order status changed from Processing to Completed.' );
	} );

	test( 'can update order details', async ( { page } ) => {
		// open order we created
		await page.goto( `wp-admin/post.php?post=${ orderId }&action=edit` );

		// update order date
		await page.fill( 'input[name=order_date]', '2018-12-14' );
		await page.click( 'button.save_order' );

		// verify changes
		await expect( page.locator( 'div.notice-success' ) ).toContainText(
			'Order updated.'
		);
		await expect( page.locator( 'input[name=order_date]' ) ).toHaveValue(
			'2018-12-14'
		);
	} );
} );

test.describe(
	'WooCommerce Orders > Edit order > Downloadable product permissions',
	() => {
		test.use( { storageState: 'e2e/storage/adminState.json' } );

		const productName = 'TDP 001';
		const product2Name = 'TDP 002';
		const customerBilling = {
			email: 'john.doe@example.com',
		};

		let productId, product2Id, noProductOrderId;

		test.beforeEach( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api
				.post( 'products', {
					name: productName,
					downloadable: true,
					download_limit: -1,
					downloads: [
						{
							id: uuid.v4(),
							name: 'Single',
							file:
								'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg',
						},
					],
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			await api
				.post( 'products', {
					name: product2Name,
					downloadable: true,
					download_limit: -1,
					downloads: [
						{
							id: uuid.v4(),
							name: 'Single',
							file:
								'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg',
						},
					],
				} )
				.then( ( response ) => {
					product2Id = response.data.id;
				} );
			await api
				.post( 'orders', {
					status: 'processing',
					line_items: [
						{
							product_id: productId,
							quantity: 1,
						},
					],
					billing: customerBilling,
				} )
				.then( ( response ) => {
					orderId = response.data.id;
				} );
			await api
				.post( 'orders', {
					status: 'processing',
					billing: customerBilling,
				} )
				.then( ( response ) => {
					noProductOrderId = response.data.id;
				} );
		} );

		test.afterEach( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.delete( `products/${ productId }`, { force: true } );
			await api.delete( `products/${ product2Id }`, { force: true } );
			await api.delete( `orders/${ orderId }`, { force: true } );
			await api.delete( `orders/${ noProductOrderId }`, { force: true } );
		} );

		test( 'can add downloadable product permissions to order without product', async ( {
			page,
		} ) => {
			// go to the order with no products
			await page.goto(
				`wp-admin/post.php?post=${ noProductOrderId }&action=edit`
			);

			// add downloadable product permissions
			await page.type( 'input.select2-search__field', productName );
			await page.click(
				'li.select2-results__option.select2-results__option--highlighted'
			);
			await page.click( 'button.grant_access' );

			// verify new downloadable product permission details
			await expect( page.locator( 'h3.fixed' ) ).toContainText(
				productName
			);
			await expect(
				page.locator( 'input[name="downloads_remaining[1]"]' )
			).toHaveAttribute( 'placeholder', 'Unlimited' );
			await expect(
				page.locator( 'input[name="access_expires[1]"]' )
			).toHaveAttribute( 'placeholder', 'Never' );
			await expect(
				page.locator( 'button.revoke_access' )
			).toBeVisible();
			await expect(
				page.locator( 'a:has-text("Copy link")' )
			).toBeVisible();
			await expect(
				page.locator( 'a:has-text("View report")' )
			).toBeVisible();
		} );

		test( 'can add downloadable product permissions to order with product', async ( {
			page,
		} ) => {
			// open the order that already has a product assigned
			await page.goto(
				`wp-admin/post.php?post=${ orderId }&action=edit`
			);

			// add downloadable product permissions
			await page.type( 'input.select2-search__field', product2Name );
			await page.click(
				'li.select2-results__option.select2-results__option--highlighted'
			);
			await page.click( 'button.grant_access' );

			// verify new downloadable product permission details
			await expect( page.locator( 'h3.fixed >> nth=1' ) ).toContainText(
				product2Name
			);
			await expect(
				page.locator( 'input[name="downloads_remaining[2]"]' )
			).toHaveAttribute( 'placeholder', 'Unlimited' );
			await expect(
				page.locator( 'input[name="access_expires[2]"]' )
			).toHaveAttribute( 'placeholder', 'Never' );
		} );

		test( 'can edit downloadable product permissions', async ( {
			page,
		} ) => {
			const expectedDownloadsRemaining = '10';
			const expectedDownloadsExpirationDate = '2050-01-01';

			// open the order that already has a product assigned
			await page.goto(
				`wp-admin/post.php?post=${ orderId }&action=edit`
			);

			// expand product download permissions
			await page.click( 'h3.fixed' );

			// edit download permissions
			await page.fill(
				'input[name="downloads_remaining[0]"]',
				expectedDownloadsRemaining
			);
			await page.fill(
				'input[name="access_expires[0]"]',
				expectedDownloadsExpirationDate
			);
			await page.click( 'button.save_order' );

			// verify new downloadable product permissions
			await page.click( 'h3.fixed' );
			await expect(
				page.locator( 'input[name="downloads_remaining[0]"]' )
			).toHaveValue( expectedDownloadsRemaining );
			await expect(
				page.locator( 'input[name="access_expires[0]"]' )
			).toHaveValue( expectedDownloadsExpirationDate );
		} );

		test( 'can revoke downloadable product permissions', async ( {
			page,
		} ) => {
			// open the order that already has a product assigned
			await page.goto(
				`wp-admin/post.php?post=${ orderId }&action=edit`
			);

			// expand product download permissions
			await page.click( 'h3.fixed' );

			// verify prior state before revoking
			await expect( page.locator( 'h3.fixed' ) ).toHaveCount( 1 );

			// click revoke access
			page.on( 'dialog', ( dialog ) => dialog.accept() );
			await page.click( 'button.revoke_access' );
			await page.waitForLoadState( 'networkidle' );

			// verify permissions gone
			await expect( page.locator( 'h3.fixed' ) ).toHaveCount( 0 );
		} );

		test( 'should not allow downloading a product if download attempts are exceeded', async ( {
			page,
		} ) => {
			const expectedReason =
				'Sorry, you have reached your download limit for this file';

			// open the order that already has a product assigned
			await page.goto(
				`wp-admin/post.php?post=${ orderId }&action=edit`
			);

			// set the download limit to 0
			// expand product download permissions
			await page.click( 'h3.fixed' );

			// edit download permissions
			await page.fill( 'input[name="downloads_remaining[0]"]', '0' );
			await page.click( 'button.save_order' );

			// get the download link
			await page.click( 'h3.fixed' );
			const downloadPage = await page
				.locator( 'a#copy-download-link' )
				.getAttribute( 'href' );

			// open download page
			await page.goto( downloadPage );
			await expect( page.locator( 'div.wp-die-message' ) ).toContainText(
				expectedReason
			);
		} );

		test( 'should not allow downloading a product if expiration date has passed', async ( {
			page,
		} ) => {
			const expectedReason = 'Sorry, this download has expired';

			// open the order that already has a product assigned
			await page.goto(
				`wp-admin/post.php?post=${ orderId }&action=edit`
			);

			// set the download limit to 0
			// expand product download permissions
			await page.click( 'h3.fixed' );

			// edit download permissions
			await page.fill( 'input[name="access_expires[0]"]', '2018-12-14' );
			await page.click( 'button.save_order' );

			// get the download link
			await page.click( 'h3.fixed' );
			const downloadPage = await page
				.locator( 'a#copy-download-link' )
				.getAttribute( 'href' );

			// open download page
			await page.goto( downloadPage );
			await expect( page.locator( 'div.wp-die-message' ) ).toContainText(
				expectedReason
			);
		} );
	}
);
