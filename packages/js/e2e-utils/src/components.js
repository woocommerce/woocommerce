/**
 * Internal dependencies
 */
import { merchant, IS_RETEST_MODE } from './flows';
import {
	uiUnblocked,
	verifyCheckboxIsUnset,
	setCheckbox,
	unsetCheckbox,
	evalAndClick,
	backboneUnblocked,
	waitForSelectorWithoutThrow,
} from './page-utils';
import factories from './factories';
import { waitForTimeout } from './flows/utils';
import { withRestApi } from './flows/with-rest-api';
import { Coupon, Order } from '@woocommerce/api';

const client = factories.api.withDefaultPermalinks;
const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has( 'products.simple.price' )
	? config.get( 'products.simple.price' )
	: '9.99';
const defaultVariableProduct = config.get( 'products.variable' );
const defaultGroupedProduct = config.get( 'products.grouped' );

const uuid = require( 'uuid' );

/**
 * Verify and publish
 *
 * @param {string} noticeText The text that appears in the notice after publishing.
 */
const verifyAndPublish = async ( noticeText ) => {
	// Wait for auto save
	await waitForTimeout( 2000 );

	// Publish product
	await expect( page ).toClick( '#publish' );
	await page.waitForSelector( '.updated.notice' );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', {
		text: noticeText,
	} );
};

/**
 * Wait for primary button to be enabled and click.
 *
 * @param {boolean} waitForNetworkIdle - Wait for network idle after click
 * @return {Promise<void>}
 */
const waitAndClickPrimary = async ( waitForNetworkIdle = true ) => {
	// Wait for "Continue" button to become active
	await page.waitForSelector( 'button.is-primary:not(:disabled)' );
	await page.click( 'button.is-primary' );
	if ( waitForNetworkIdle ) {
		await page.waitForNavigation( { waitUntil: 'networkidle0' } );
	}
};

/**
 * Create simple product.
 *
 * @param {string} productTitle    Defaults to Simple Product. Customizable title.
 * @param {string} productPrice    Defaults to $9.99. Customizable pricing.
 * @param {Object} additionalProps Defaults to nothing. Additional product properties.
 */
const createSimpleProduct = async (
	productTitle = simpleProductName,
	productPrice = simpleProductPrice,
	additionalProps = {}
) => {
	const newProduct = {
		name: productTitle,
		regularPrice: productPrice,
		...additionalProps,
	};
	const product = await factories.products.simple.create( newProduct );
	return product.id;
};

/**
 * Create simple product with categories
 *
 * @param {string} productName  Product's name which can be changed when writing a test
 * @param {string} productPrice Product's price which can be changed when writing a test
 * @param {string} categoryName Product's category which can be changed when writing a test
 */
const createSimpleProductWithCategory = async (
	productName,
	productPrice,
	categoryName
) => {
	// Get the category ID so we can add it to the product below
	const categoryId = await withRestApi.createProductCategory( categoryName );

	const product = await factories.products.simple.create( {
		name: productName,
		regularPrice: productPrice,
		categories: [
			{
				id: categoryId,
			},
		],
		isVirtual: true,
	} );

	return product.id;
};

/**
 * Create simple downloadable product
 *
 * @param {string} name          Product's name. Defaults to 'Simple Product' (see createSimpleProduct definition).
 * @param {number} downloadLimit Product's download limit. Defaults to '-1' (unlimited).
 * @param {string} downloadName  Product's download name. Defaults to 'Single'.
 * @param {string} price         Product's price. Defaults to '$9.99' (see createSimpleProduct definition).
 */
const createSimpleDownloadableProduct = async (
	name,
	downloadLimit = -1,
	downloadName = 'Single',
	price
) => {
	const productDownloadDetails = {
		downloadable: true,
		downloads: [
			{
				id: uuid.v4(),
				name: downloadName,
				file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg',
			},
		],
		download_limit: downloadLimit,
	};

	return await createSimpleProduct( name, price, productDownloadDetails );
};

/**
 * Create variable product.
 * Also, create variations for all attributes.
 *
 * @param {Object} varProduct Defaults to the variable product object in `default.json`
 * @return {number} the ID of the created variable product
 */
const createVariableProduct = async ( varProduct = defaultVariableProduct ) => {
	const { attributes } = varProduct;
	const { id } = await factories.products.variable.create( varProduct ); // create the variable product
	const variations = [];
	const buffer = []; // accumulated attributes while looping
	const aIdx = 0; // attributes[] index

	// Create variation for all attributes
	// eslint-disable-next-line no-shadow
	const createVariation = ( aIdx ) => {
		const { name, options } = attributes[ aIdx ];
		const isLastAttribute = aIdx === attributes.length - 1;

		// Add each attribute value to the buffer.
		options.forEach( ( opt ) => {
			buffer.push( {
				name,
				option: opt,
			} );

			if ( isLastAttribute ) {
				// If this is the last attribute, it means the variation is now complete.
				// Save whatever's been accumulated in the buffer to the `variations[]` array.
				variations.push( {
					attributes: [ ...buffer ],
				} );
			} else {
				// Otherwise, move to the next attribute first
				// before proceeding to the next value in this attribute.
				createVariation( aIdx + 1 );
			}

			buffer.pop();
		} );
	};
	createVariation( aIdx );

	// Set some properties of 1st variation
	variations[ 0 ].regularPrice = '9.99';
	variations[ 0 ].virtual = true;

	// Set some properties of 2nd variation
	variations[ 1 ].regularPrice = '11.99';
	variations[ 1 ].virtual = true;

	// Set some properties of 3rd variation
	variations[ 2 ].regularPrice = '20';
	variations[ 2 ].weight = '200';
	variations[ 2 ].dimensions = {
		length: '10',
		width: '20',
		height: '15',
	};
	variations[ 2 ].manage_stock = true;

	// Use API to create each variation
	for ( const v of variations ) {
		await factories.products.variation.create( {
			productId: id,
			variation: v,
		} );
	}

	return id;
};

/**
 * Create grouped product.
 *
 * @param {Object} groupedProduct Defaults to the grouped product object in `default.json`
 * @return {number} ID of the grouped product
 */
const createGroupedProduct = async (
	groupedProduct = defaultGroupedProduct
) => {
	const { name, groupedProducts } = groupedProduct;
	const simpleProductIds = [];
	let groupedProductRequest;

	// Using the api, create simple products to be grouped
	for ( const simpleProduct of groupedProducts ) {
		const { id } = await factories.products.simple.create( simpleProduct );
		simpleProductIds.push( id );
	}

	// Using the api, create the grouped product
	// eslint-disable-next-line prefer-const
	groupedProductRequest = {
		name,
		groupedProducts: simpleProductIds,
	};
	const { id } = await factories.products.grouped.create(
		groupedProductRequest
	);

	return id;
};

/**
 * Use the API to create an order with the provided details.
 *
 * @param {Object} orderOptions
 * @return {Promise<number>} ID of the created order.
 */
const createOrder = async ( orderOptions = {} ) => {
	const newOrder = {
		...( orderOptions.status && { status: orderOptions.status } ),
		...( orderOptions.customerId && {
			customer_id: orderOptions.customerId,
		} ),
		...( orderOptions.customerBilling && {
			billing: orderOptions.customerBilling,
		} ),
		...( orderOptions.customerShipping && {
			shipping: orderOptions.customerShipping,
		} ),
		...( orderOptions.productId && {
			line_items: [ { product_id: orderOptions.productId } ],
		} ),
		...( orderOptions.lineItems && {
			line_items: orderOptions.lineItems,
		} ),
	};

	const repository = Order.restRepository( client );
	const order = await repository.create( newOrder );

	return order.id;
};

/**
 * Create a basic order with the provided order status.
 *
 * @param {string} orderStatus Status of the new order. Defaults to `Pending payment`.
 */
const createSimpleOrder = async ( orderStatus = 'Pending payment' ) => {
	// Go to 'Add new order' page
	await merchant.openNewOrder();

	// Make sure we're on the add order page
	await expect( page.title() ).resolves.toMatch( 'Add new order' );

	// Set order status
	await expect( page ).toSelect( '#order_status', orderStatus );

	// Wait for auto save
	await page.waitFor( 2000 );

	// Create the order
	await expect( page ).toClick( 'button.save_order' );
	await page.waitForSelector( '#message' );

	// Verify
	await expect( page ).toMatchElement( '#message', {
		text: 'Order updated.',
	} );

	const variablePostId = await page.$( '#post_ID' );
	const variablePostIdValue = await (
		await variablePostId.getProperty( 'value' )
	).jsonValue();
	return variablePostIdValue;
};

/**
 * Creates a batch of orders from the given `statuses`
 * using the "Batch Create Order" API.
 *
 * @param {Array} statuses Array of order statuses
 */
const batchCreateOrders = async ( statuses ) => {
	const defaultOrder = config.get( 'orders.basicPaidOrder' );
	const path = '/wc/v3/orders/batch';

	// Create an order per status
	const orders = statuses.map( ( s ) => {
		return {
			...defaultOrder,
			status: s,
		};
	} );

	// Set the request payload from the created orders.
	// Then send the API request.
	const payload = { create: orders };
	const response = await client.post( path, payload );
	expect( response.status ).toEqual( 200 );
};

/**
 * Adds a product to an order in the merchant.
 *
 * @param {number} orderId     ID of the order to add the product to.
 * @param {string} productName Name of the product being added to the order.
 */
const addProductToOrder = async ( orderId, productName ) => {
	await merchant.goToOrder( orderId );

	// Add a product to the order
	await expect( page ).toClick( 'button.add-line-item' );
	await expect( page ).toClick( 'button.add-order-item' );
	await page.waitForSelector( '.wc-backbone-modal-header' );
	await expect( page ).toClick(
		'.wc-backbone-modal-content .wc-product-search'
	);
	await expect( page ).toFill(
		'#wc-backbone-modal-dialog + .select2-container .select2-search__field',
		productName
	);
	await page.waitForSelector( 'li[aria-selected="true"]', {
		timeout: 10000,
	} );
	await expect( page ).toClick( 'li[aria-selected="true"]' );
	await page.click( '.wc-backbone-modal-content #btn-ok' );

	await backboneUnblocked();

	// Verify the product we added shows as a line item now
	await expect( page ).toMatchElement( '.wc-order-item-name', {
		text: productName,
	} );
};

/**
 * Creates a basic coupon with the provided coupon amount. Returns the coupon code.
 *
 * @param {string} couponAmount Amount to be applied. Defaults to 5.
 * @param {string} discountType Type of a coupon. Defaults to Fixed cart discount.
 */
const createCoupon = async (
	couponAmount = '5',
	discountType = 'Fixed cart discount'
) => {
	let couponType;
	switch ( discountType ) {
		case 'Fixed cart discount':
			couponType = 'fixed_cart';
			break;
		case 'Fixed product discount':
			couponType = 'fixed_product';
			break;
		case 'Percentage discount':
			couponType = 'percent';
			break;
		default:
			couponType = discountType;
	}

	// Fill in coupon code
	const couponCode = 'code-' + couponType + new Date().getTime().toString();
	const repository = Coupon.restRepository( client );
	await repository.create( {
		code: couponCode,
		discountType: couponType,
		amount: couponAmount,
	} );

	return couponCode;
};

/**
 * Adds a shipping zone along with a shipping method.
 *
 * @param {string} zoneName     Shipping zone name.
 * @param {string} zoneLocation Shipping zone location. Defaults to country:US. For states use: state:US:CA
 * @param {string} zipCode      Shipping zone zip code. Defaults to empty one space.
 * @param {string} zoneMethod   Shipping method type. Defaults to flat_rate (use also: free_shipping or local_pickup)
 */
const addShippingZoneAndMethod = async (
	zoneName,
	zoneLocation = 'country:US',
	zipCode = ' ',
	zoneMethod = 'flat_rate'
) => {
	await merchant.openNewShipping();

	// Fill shipping zone name
	await page.waitForSelector( 'input#zone_name' );
	await expect( page ).toFill( 'input#zone_name', zoneName );

	// Select shipping zone location
	await expect( page ).toSelect(
		'select[name="zone_locations"]',
		zoneLocation
	);

	await uiUnblocked();

	// Fill shipping zone postcode if needed otherwise just put empty space
	await page.waitForSelector( 'a.wc-shipping-zone-postcodes-toggle' );
	await expect( page ).toClick( 'a.wc-shipping-zone-postcodes-toggle' );
	await expect( page ).toFill( '#zone_postcodes', zipCode );
	await expect( page ).toMatchElement( '#zone_postcodes', zipCode );
	await expect( page ).toClick( 'button#submit' );

	await uiUnblocked();

	// Add shipping zone method
	await page.waitFor( 1000 );
	await expect( page ).toClick( 'button.wc-shipping-zone-add-method', {
		text: 'Add shipping method',
	} );
	await page.waitForSelector( '.wc-shipping-zone-method-selector' );
	await expect( page ).toSelect( 'select[name="add_method_id"]', zoneMethod );
	await expect( page ).toClick( 'button#btn-ok' );
	await page.waitForSelector( '#zone_locations' );

	await uiUnblocked();
};

/**
 * Click the Update button on the order details page.
 *
 * @param {string}  noticeText  The text that appears in the notice after updating the order.
 * @param {boolean} waitForSave Optionally wait for auto save.
 */
const clickUpdateOrder = async ( noticeText, waitForSave = false ) => {
	if ( waitForSave ) {
		await page.waitFor( 2000 );
	}

	// Update order
	await expect( page ).toClick( 'button.save_order' );
	await page.waitForSelector( '.updated.notice' );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', {
		text: noticeText,
	} );
};

/**
 * Delete all email logs in the WP Mail Logging plugin page.
 */
const deleteAllEmailLogs = async () => {
	await merchant.openEmailLog();

	// Make sure we have emails to delete. If we don't, this selector will return null.
	if ( ( await page.$( '#bulk-action-selector-top' ) ) !== null ) {
		await setCheckbox( '#cb-select-all-1' );
		await expect( page ).toSelect( '#bulk-action-selector-top', 'Delete' );
		await Promise.all( [
			page.click( '#doaction' ),
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	}
};

/**
 * Delete all the existing shipping zones.
 */
const deleteAllShippingZones = async () => {
	await merchant.openSettings( 'shipping' );

	// Delete existing shipping zones.
	try {
		let zone = await page.$( '.wc-shipping-zone-delete' );
		if ( zone ) {
			// WP action links aren't clickable because they are hidden with a left=-9999 style.
			await page.evaluate( () => {
				document.querySelector(
					'.wc-shipping-zone-name .row-actions'
				).style.left = '0';
			} );
			while ( zone ) {
				await evalAndClick( '.wc-shipping-zone-delete' );
				await uiUnblocked();
				zone = await page.$( '.wc-shipping-zone-delete' );
			}
		}
	} catch ( error ) {
		// Prevent an error here causing the test to fail.
	}
};

export {
	createSimpleProduct,
	createVariableProduct,
	createGroupedProduct,
	createSimpleOrder,
	verifyAndPublish,
	addProductToOrder,
	createCoupon,
	addShippingZoneAndMethod,
	createSimpleProductWithCategory,
	createSimpleDownloadableProduct,
	clickUpdateOrder,
	deleteAllEmailLogs,
	deleteAllShippingZones,
	batchCreateOrders,
	createOrder,
};
