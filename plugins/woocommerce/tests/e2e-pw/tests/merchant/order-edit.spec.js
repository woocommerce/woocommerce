const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const uuid = require( 'uuid' );

test.describe( 'Edit order', { tag: [ '@services', '@hpos' ] }, () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	let orderId, secondOrderId, orderToCancel, customerId;

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
		await api
			.post( 'orders', {
				status: 'processing',
			} )
			.then( ( response ) => {
				secondOrderId = response.data.id;
			} );
		await api
			.post( 'orders', {
				status: 'processing',
			} )
			.then( ( response ) => {
				orderToCancel = response.data.id;
			} );
		await api
			.post( 'customers', {
				email: 'archie123@email.addr',
				first_name: 'Archie',
				last_name: 'Greenback',
				username: 'big.archie',
				billing: {
					first_name: 'Archibald',
					last_name: 'Greenback',
					company: 'Automattic',
					country: 'US',
					address_1: 'Billing Address 1',
					address_2: 'Billing Address 2',
					city: 'San Francisco',
					state: 'CA',
					postcode: '94107',
					phone: '123456789',
					email: 'archie123@email.addr',
				},
				shipping: {
					first_name: 'Shipping First',
					last_name: 'Shipping Last',
					company: 'Automattic',
					country: 'US',
					address_1: 'Shipping Address 1',
					address_2: 'Shipping Address 2',
					city: 'San Francisco',
					state: 'CA',
					postcode: '94107',
				},
			} )
			.then( ( response ) => {
				customerId = response.data.id;
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
		await api.delete( `orders/${ secondOrderId }`, { force: true } );
		await api.delete( `orders/${ orderToCancel }`, { force: true } );
		await api.delete( `customers/${ customerId }`, { force: true } );
	} );

	test( 'can view single order', async ( { page } ) => {
		if ( process.env.DISABLE_HPOS === '1' ) {
			await page.goto( 'wp-admin/edit.php?post_type=shop_order' );
		} else {
			await page.goto( '/wp-admin/admin.php?page=wc-orders' );
		}

		// confirm we're on the orders page
		await expect( page.locator( 'h1.components-text' ) ).toContainText(
			'Orders'
		);
		// open order we created
		await page.goto(
			`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
		);

		// make sure we're on the order details page
		await expect( page.locator( 'h1.wp-heading-inline' ) ).toContainText(
			/Edit [oO]rder/
		);
	} );

	test( 'can update order status', async ( { page } ) => {
		// open order we created
		await page.goto(
			`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
		);

		// update order status to Completed
		await page.locator( '#order_status' ).selectOption( 'wc-completed' );
		await page.locator( 'button.save_order' ).click();

		// verify order status changed and note added
		await expect( page.locator( '#order_status' ) ).toHaveValue(
			'wc-completed'
		);
		await expect(
			page.locator( '#woocommerce-order-notes .note_content >> nth=0' )
		).toContainText( 'Order status changed from Processing to Completed.' );

		// load the orders listing and confirm order is completed
		await page.goto( '/wp-admin/admin.php?page=wc-orders' );

		await expect(
			page
				.locator( `:is(#order-${ orderId }, #post-${ orderId })` )
				.getByRole( 'cell', { name: 'Completed' } )
		).toBeVisible();
	} );

	test( 'can update order status to cancelled', async ( { page } ) => {
		// open order we created
		await page.goto(
			`/wp-admin/post.php?post=${ orderToCancel }&action=edit`
		);

		// update order status to Completed
		await page.locator( '#order_status' ).selectOption( 'Cancelled' );
		await page.locator( 'button.save_order' ).click();

		// verify order status changed and note added
		await expect( page.locator( '#order_status' ) ).toHaveValue(
			'wc-cancelled'
		);
		await expect(
			page.getByText(
				'Order status changed from Processing to Cancelled.'
			)
		).toBeVisible();

		// load the orders listing and confirm order is cancelled
		await page.goto( '/wp-admin/admin.php?page=wc-orders' );

		await expect(
			page
				.locator(
					`:is(#order-${ orderToCancel }, #post-${ orderToCancel })`
				)
				.getByRole( 'cell', { name: 'Cancelled' } )
		).toBeVisible();
	} );

	test( 'can update order details', async ( { page } ) => {
		// open order we created
		await page.goto(
			`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
		);

		// update order date
		await page.locator( 'input[name=order_date]' ).fill( '2018-12-14' );
		await page.locator( 'button.save_order' ).click();

		// verify changes
		await expect(
			page
				.locator( 'div.notice-success > p' )
				.filter( { hasText: 'Order updated.' } )
		).toBeVisible();
		await expect( page.locator( 'input[name=order_date]' ) ).toHaveValue(
			'2018-12-14'
		);
	} );

	test( 'can add and delete order notes', async ( { page } ) => {
		// open order we created
		await page.goto(
			`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
		);
		page.on( 'dialog', ( dialog ) => dialog.accept() );

		// add an order note
		await page
			.getByLabel( 'Add note' )
			.fill(
				'This order is a test order. It is only a test. This note is a private note.'
			);
		await page.getByRole( 'button', { name: 'Add', exact: true } ).click();

		// verify the note saved
		await expect(
			page.getByText(
				'This order is a test order. It is only a test. This note is a private note.'
			)
		).toBeVisible();

		// delete the note
		await page
			.getByRole( 'button', { name: 'Delete note' } )
			.first()
			.click();

		// verify the note is gone
		await expect(
			page.getByText(
				'This order is a test order. It is only a test. This note is a private note.'
			)
		).toBeHidden();

		// add note to customer
		// add an order note
		await page
			.getByLabel( 'Add note' )
			.fill(
				'This order is a test order. It is only a test. This note is a note to the customer.'
			);
		await page.getByLabel( 'Note type' ).selectOption( 'Note to customer' );
		await page.getByRole( 'button', { name: 'Add', exact: true } ).click();

		// verify the note saved
		await expect(
			page.getByText(
				'This order is a test order. It is only a test. This note is a note to the customer.'
			)
		).toBeVisible();

		// delete the note
		await page
			.getByRole( 'button', { name: 'Delete note' } )
			.first()
			.click();

		// verify the note is gone
		await expect(
			page.getByText(
				'This order is a test order. It is only a test. This note is a private note.'
			)
		).toBeHidden();
	} );

	test( 'can load billing and shipping details', async ( { page } ) => {
		// Open our test order and select the customer we just created.
		await test.step( 'Open our test order and select the customer we just created.', async () => {
			await page.goto(
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// Assign customer
			await page.locator( '#select2-customer_user-container' ).click();
			await page
				.getByRole( 'combobox' )
				.nth( 4 )
				.pressSequentially( 'big.archie' );
			await page.waitForSelector( 'li.select2-results__option' );
			await page.locator( 'li.select2-results__option' ).click();
		} );

		await test.step( 'Load the billing and shipping addresses', async () => {
			// Click the load billing address button
			await page
				.getByRole( 'link', { name: 'Load billing address' } )
				.click();
			await expect(
				page.locator( '[id="_billing_first_name"]' )
			).toHaveValue( 'Archibald' );

			// Click the load shipping address button
			await page
				.getByRole( 'link', { name: 'Load shipping address' } )
				.click();
			await expect(
				page.locator( '[id="_shipping_first_name"]' )
			).toHaveValue( 'Shipping First' );
		} );

		await test.step( 'Save the order and confirm addresses saved', async () => {
			// Save the order
			await page.locator( 'button.save_order' ).click();

			// Verify both addresses are saved
			await expect(
				page.getByText(
					'Billing Edit Load billing address Archibald GreenbackAutomatticBilling Address'
				)
			).toBeVisible();
			await expect(
				page.getByText(
					'Shipping Edit Load shipping address Copy billing address Shipping First'
				)
			).toBeVisible();
		} );
	} );

	test( 'can copy billing address to shipping address', async ( {
		page,
	} ) => {
		// click ok on the dialog that pops up
		page.on( 'dialog', ( dialog ) => dialog.accept() );

		await test.step( 'Open our second test order and select the customer we just created.', async () => {
			// Open our second test order
			await page.goto(
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ secondOrderId }`
			);

			// Assign customer
			await page.locator( '#select2-customer_user-container' ).click();
			await page
				.getByRole( 'combobox' )
				.nth( 4 )
				.pressSequentially( 'big.archie' );
			await page.waitForSelector( 'li.select2-results__option' );
			await page.locator( 'li.select2-results__option' ).click();
		} );

		await test.step( 'Load the billing address and then copy it to the shipping address', async () => {
			// Click the load billing address button
			await page
				.getByRole( 'link', { name: 'Load billing address' } )
				.click();
			await expect(
				page.locator( '[id="_billing_first_name"]' )
			).toHaveValue( 'Archibald' );

			// Click the copy billing address to shipping address button
			await page
				.getByRole( 'link', { name: 'Copy billing address' } )
				.click();
			await expect(
				page.locator( '[id="_shipping_first_name"]' )
			).toHaveValue( 'Archibald' );
		} );

		await test.step( 'Save the order and confirm addresses saved', async () => {
			// Save the order
			await page.locator( 'button.save_order' ).click();

			// Verify both addresses are saved
			await expect(
				page.getByText(
					'Billing Edit Load billing address Archibald GreenbackAutomatticBilling Address'
				)
			).toBeVisible();
			await expect(
				page.getByText(
					'Shipping Edit Load shipping address Copy billing address Archibald'
				)
			).toBeVisible();
		} );
	} );
} );

test.describe(
	'Edit order > Downloadable product permissions',
	{ tag: [ '@services', '@hpos' ] },
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );

		const productName = 'TDP 001';
		const product2Name = 'TDP 002';
		const customerBilling = {
			email: 'john.doe@example.com',
		};

		let orderId,
			productId,
			product2Id,
			noProductOrderId,
			initialGrantAccessAfterPaymentSetting;

		/**
		 * Enable the "Grant access to downloadable products after payment" setting in WooCommerce > Settings > Products > Downloadable products.
		 */
		const enableGrantAccessAfterPaymentSetting = async ( api ) => {
			const endpoint =
				'settings/products/woocommerce_downloads_grant_access_after_payment';

			// Get current value
			const response = await api.get( endpoint );
			initialGrantAccessAfterPaymentSetting = response.data.value;

			// Enable
			if ( initialGrantAccessAfterPaymentSetting !== 'yes' ) {
				await api.put( endpoint, {
					value: 'yes',
				} );
			}
		};

		const revertGrantAccessAfterPaymentSetting = async ( api ) => {
			const endpoint =
				'settings/products/woocommerce_downloads_grant_access_after_payment';

			await api.put( endpoint, {
				value: initialGrantAccessAfterPaymentSetting,
			} );
		};

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
							file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg',
						},
					],
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );

			await enableGrantAccessAfterPaymentSetting( api );

			await api
				.post( 'products', {
					name: product2Name,
					downloadable: true,
					download_limit: -1,
					downloads: [
						{
							id: uuid.v4(),
							name: 'Single',
							file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg',
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
			await revertGrantAccessAfterPaymentSetting( api );
		} );

		// these tests aren't completely independent. Needs some refactoring.

		test( 'can add downloadable product permissions to order without product', async ( {
			page,
		} ) => {
			// go to the order with no products
			await page.goto(
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ noProductOrderId }`
			);

			// add downloadable product permissions
			await page
				.locator( 'input.select2-search__field' )
				.type( productName );
			await page
				.locator(
					'li.select2-results__option.select2-results__option--highlighted'
				)
				.click();
			await page.locator( 'button.grant_access' ).click();

			// verify new downloadable product permission details
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
			).toContainText( productName );
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(1) > input.short'
				)
			).toHaveAttribute( 'placeholder', 'Unlimited' );
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(2) > input.short'
				)
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
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// add downloadable product permissions
			await page
				.locator( 'input.select2-search__field' )
				.type( product2Name );
			await page
				.locator(
					'li.select2-results__option.select2-results__option--highlighted'
				)
				.click();
			await page.locator( 'button.grant_access' ).click();

			// verify new downloadable product permission details
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong',
					{ hasText: product2Name }
				)
			).toBeVisible();

			await expect(
				page.locator(
					'#woocommerce-order-downloads input[name^="downloads_remaining"] >> nth=-1'
				)
			).toHaveAttribute( 'placeholder', 'Unlimited' );
			await expect(
				page.locator(
					'#woocommerce-order-downloads input[name^="access_expires"] >> nth=-1'
				)
			).toHaveAttribute( 'placeholder', 'Never' );
		} );

		test( 'can edit downloadable product permissions', async ( {
			page,
		} ) => {
			const expectedDownloadsRemaining = '10';
			const expectedDownloadsExpirationDate = '2050-01-01';

			// open the order that already has a product assigned
			await page.goto(
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// expand product download permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
				.click();

			// edit download permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(1) > input.short'
				)
				.fill( expectedDownloadsRemaining );
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(2) > input.short'
				)
				.fill( expectedDownloadsExpirationDate );
			await page.locator( 'button.save_order' ).click();

			// verify new downloadable product permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
				.click();
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(1) > input.short'
				)
			).toHaveValue( expectedDownloadsRemaining );
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(2) > input.short'
				)
			).toHaveValue( expectedDownloadsExpirationDate );
		} );

		test( 'can revoke downloadable product permissions', async ( {
			page,
		} ) => {
			// open the order that already has a product assigned
			await page.goto(
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// expand product download permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
				.click();

			// verify prior state before revoking
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
			).toHaveCount( 1 );

			// click revoke access
			page.on( 'dialog', ( dialog ) => dialog.accept() );
			await page.locator( 'button.revoke_access' ).click();

			// verify permissions gone
			await expect(
				page.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
			).toHaveCount( 0 );
		} );

		test( 'should not allow downloading a product if download attempts are exceeded', async ( {
			page,
		} ) => {
			const expectedReason =
				'Sorry, you have reached your download limit for this file';

			// open the order that already has a product assigned
			await page.goto(
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// set the download limit to 0
			// expand product download permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
				.click();

			// edit download permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(1) > input.short'
				)
				.fill( '0' );
			await page.locator( 'button.save_order' ).click();

			// get the download link
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
				.click();
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
				`/wp-admin/admin.php?page=wc-orders&action=edit&id=${ orderId }`
			);

			// set the download limit to 0
			// expand product download permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
				.click();

			// edit download permissions
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > table > tbody > tr > td:nth-child(2) > input.short'
				)
				.fill( '2018-12-14' );
			await page.locator( 'button.save_order' ).click();

			// get the download link
			await page
				.locator(
					'#woocommerce-order-downloads > div.inside > div > div.wc-metaboxes > div > h3 > strong'
				)
				.click();
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
