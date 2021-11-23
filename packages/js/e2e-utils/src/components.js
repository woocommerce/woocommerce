/**
 * @format
 */

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
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';
const defaultVariableProduct = config.get('products.variable');
const defaultGroupedProduct = config.get('products.grouped');

/**
 * Verify and publish
 *
 * @param noticeText The text that appears in the notice after publishing.
 */
const verifyAndPublish = async ( noticeText ) => {
	// Wait for auto save
	await waitForTimeout( 2000 );

	// Publish product
	await expect( page ).toClick( '#publish' );
	await page.waitForSelector( '.updated.notice' );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: noticeText } );
};

/**
 * Wait for primary button to be enabled and click.
 *
 * @param waitForNetworkIdle - Wait for network idle after click
 * @returns {Promise<void>}
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
 * Complete onboarding wizard.
 */
const completeOnboardingWizard = async () => {
	// Store Details section
	await merchant.runSetupWizard();

	// Fill store's address - first line
	await expect( page ).toFill( '#inspector-text-control-0', config.get( 'addresses.admin.store.addressfirstline' ) );

	// Fill store's address - second line
	await expect( page ).toFill( '#inspector-text-control-1', config.get( 'addresses.admin.store.addresssecondline' ) );

	// Fill country and state where the store is located
	await expect( page ).toFill( '.woocommerce-select-control__control-input', config.get( 'addresses.admin.store.countryandstate' ) );

	// Fill the city where the store is located
	await expect( page ).toFill( '#inspector-text-control-2', config.get( 'addresses.admin.store.city' ) );

	// Fill postcode of the store
	await expect( page ).toFill( '#inspector-text-control-3', config.get( 'addresses.admin.store.postcode' ) );

	// Verify that checkbox next to "I'm setting up a store for a client" is not selected
	await verifyCheckboxIsUnset( '.components-checkbox-control__input' );

	// Wait for "Continue" button to become active
	await page.waitForSelector( 'button.is-primary:not(:disabled)' );

	// Click on "Continue" button to move to the next step
	await page.click( 'button.is-primary', { text: 'Continue' } );

	// Wait for usage tracking pop-up window to appear on a new site
	const usageTrackingHeader = await page.$('.components-modal__header-heading');
	if ( usageTrackingHeader ) {
		await expect(page).toMatchElement(
			'.components-modal__header-heading', {text: 'Build a better WooCommerce'}
		);

		// Query for "No Thanks" buttons
		const continueButtons = await page.$$( '.woocommerce-usage-modal__actions button.is-secondary' );
		expect( continueButtons ).toHaveLength( 1 );

		await continueButtons[0].click();
	}
	await page.waitForNavigation( { waitUntil: 'networkidle0' } );

	// Industry section

	// Query for the industries checkboxes
	const industryCheckboxes = await page.$$( '.components-checkbox-control__input' );
	expect( industryCheckboxes ).toHaveLength( 8 );

	// Select all industries including "Other"
	for ( let i = 0; i < 8; i++ ) {
		await industryCheckboxes[i].click();
	}

	// Fill "Other" industry
	await expect( page ).toFill( '.components-text-control__input', config.get( 'onboardingwizard.industry' ) );

	// Wait for "Continue" button to become active
	await waitAndClickPrimary();

	// Product types section

	// Query for the product types checkboxes
	const productTypesCheckboxes = await page.$$( '.components-checkbox-control__input' );
	expect( productTypesCheckboxes ).toHaveLength( 7 );

	// Select Physical and Downloadable products
	for ( let i = 1; i < 2; i++ ) {
		await productTypesCheckboxes[i].click();
	}

	// Wait for "Continue" button to become active
	await waitAndClickPrimary();

	// Business Details section

	// Temporarily add delay to reduce test flakiness
	await page.waitFor( 2000 );

	// Query for the <SelectControl>s
	const selectControls = await page.$$( '.woocommerce-select-control' );
	expect( selectControls ).toHaveLength( 2 );

	// Fill the number of products you plan to sell
	await selectControls[0].click();
	await page.waitForSelector( '.woocommerce-select-control__control' );
	await expect( page ).toClick( '.woocommerce-select-control__option', { text: config.get( 'onboardingwizard.numberofproducts' ) } );

	// Fill currently selling elsewhere
	await selectControls[1].click();
	await page.waitForSelector( '.woocommerce-select-control__control' );
	await expect( page ).toClick( '.woocommerce-select-control__option', { text: config.get( 'onboardingwizard.sellingelsewhere' ) } );

	// Wait for "Continue" button to become active
	await waitAndClickPrimary( false );

	// Skip installing extensions
	await unsetCheckbox( '.components-checkbox-control__input' );
	await verifyCheckboxIsUnset( '.components-checkbox-control__input' );
	await waitAndClickPrimary();

	// Theme section
	await waitAndClickPrimary();

	// End of onboarding wizard
	if ( IS_RETEST_MODE ) {
		// Home screen modal can't be reset via the rest api.
		return;
	}

	// Wait for homescreen welcome modal to appear
	let welcomeHeader = await waitForSelectorWithoutThrow( '.woocommerce__welcome-modal__page-content' );
	if ( ! welcomeHeader ) {
		return;
	}

	// Click two Next buttons
	for ( let b = 0; b < 2; b++ ) {
		await page.waitForSelector('button.components-guide__forward-button');
		await page.click('button.components-guide__forward-button');
	}
	// Wait for "Let's go" button to become active
	await page.waitForSelector( 'button.components-guide__finish-button' );
	await page.click( 'button.components-guide__finish-button' );
};

/**
 * Create simple product.
 *
 * @param {string} productTitle Defaults to Simple Product. Customizable title.
 * @param {string} productPrice Defaults to $9.99. Customizable pricing.
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
 * @param productName Product's name which can be changed when writing a test
 * @param productPrice Product's price which can be changed when writing a test
 * @param categoryName Product's category which can be changed when writing a test
 */
const createSimpleProductWithCategory = async ( productName, productPrice, categoryName ) => {
	// Get the category ID so we can add it to the product below
	const categoryId = await withRestApi.createProductCategory( categoryName );

	const product = await factories.products.simple.create( {
		name: productName,
		regularPrice: productPrice,
		categories: [
			{
				id: categoryId,
			}
		],
		isVirtual: true,
	} );

	return product.id;
};

/**
 * Create variable product.
 * Also, create variations for all attributes.
 *
 * @param varProduct Defaults to the variable product object in `default.json`
 * @returns the ID of the created variable product
 */
const createVariableProduct = async (varProduct = defaultVariableProduct) => {
	const { attributes } = varProduct;
	const { id } = await factories.products.variable.create(varProduct); // create the variable product
	const variations = [];
	const buffer = []; // accumulated attributes while looping
	const aIdx = 0; // attributes[] index

	// Create variation for all attributes
	const createVariation = (aIdx) => {
		const { name, options } = attributes[aIdx];
		const isLastAttribute = aIdx === attributes.length - 1;

		// Add each attribute value to the buffer.
		options.forEach((opt) => {
			buffer.push({
				name: name,
				option: opt
			});

			if (isLastAttribute) {
				// If this is the last attribute, it means the variation is now complete.
				// Save whatever's been accumulated in the buffer to the `variations[]` array.
				variations.push({
					attributes: [...buffer]
				});
			} else {
				// Otherwise, move to the next attribute first
				// before proceeding to the next value in this attribute.
				createVariation(aIdx + 1);
			}

			buffer.pop();
		});
	};
	createVariation(aIdx);

	// Set some properties of 1st variation
	variations[0].regularPrice = '9.99';
	variations[0].virtual = true;

	// Set some properties of 2nd variation
	variations[1].regularPrice = '11.99';
	variations[1].virtual = true;

	// Set some properties of 3rd variation
	variations[2].regularPrice = '20';
	variations[2].weight = '200';
	variations[2].dimensions = {
		length: '10',
		width: '20',
		height: '15'
	};
	variations[2].manage_stock = true;

	// Use API to create each variation
	for (const v of variations) {
		await factories.products.variation.create({
			productId: id,
			variation: v
		});
	}

	return id;
};

/**
 * Create grouped product.
 *
 * @param groupedProduct Defaults to the grouped product object in `default.json`
 * @returns ID of the grouped product
 */
const createGroupedProduct = async (groupedProduct = defaultGroupedProduct) => {
	const { name, groupedProducts } = groupedProduct;
	const simpleProductIds = [];
	let groupedProductRequest;

	// Using the api, create simple products to be grouped
	for (const simpleProduct of groupedProducts) {
		const { id } = await factories.products.simple.create(simpleProduct);
		simpleProductIds.push(id);
	}

	// Using the api, create the grouped product
	groupedProductRequest = {
		name: name,
		groupedProducts: simpleProductIds
	};
	const { id } = await factories.products.grouped.create(
		groupedProductRequest
	);

	return id;
};

/**
 * Use the API to create an order with the provided details.
 *
 * @param {object} orderOptions
 * @returns {Promise<number>} ID of the created order.
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
 * @param orderStatus Status of the new order. Defaults to `Pending payment`.
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
	await expect( page ).toMatchElement( '#message', { text: 'Order updated.' } );

	const variablePostId = await page.$( '#post_ID' );
	let variablePostIdValue = ( await ( await variablePostId.getProperty( 'value' ) ).jsonValue() );
	return variablePostIdValue;
};

/**
 * Creates a batch of orders from the given `statuses`
 * using the "Batch Create Order" API.
 *
 * @param statuses Array of order statuses
 */
const batchCreateOrders = async (statuses) => {
	const defaultOrder = config.get('orders.basicPaidOrder');
	const path = '/wc/v3/orders/batch';

	// Create an order per status
	const orders = statuses.map((s) => {
		return {
			...defaultOrder,
			status: s
		};
	});

	// Set the request payload from the created orders.
	// Then send the API request.
	const payload = { create: orders };
	const response = await client.post(path, payload);
	expect( response.status ).toEqual(200);
};

/**
 * Adds a product to an order in the merchant.
 *
 * @param orderId ID of the order to add the product to.
 * @param productName Name of the product being added to the order.
 */
const addProductToOrder = async ( orderId, productName ) => {
	await merchant.goToOrder( orderId );

	// Add a product to the order
	await expect( page ).toClick( 'button.add-line-item' );
	await expect( page ).toClick( 'button.add-order-item' );
	await page.waitForSelector( '.wc-backbone-modal-header' );
	await expect( page ).toClick( '.wc-backbone-modal-content .wc-product-search' );
	await expect( page ).toFill('#wc-backbone-modal-dialog + .select2-container .select2-search__field', productName);
	await page.waitForSelector( 'li[aria-selected="true"]', { timeout: 10000 } );
	await expect( page ).toClick( 'li[aria-selected="true"]' );
	await page.click( '.wc-backbone-modal-content #btn-ok' );

	await backboneUnblocked();

	// Verify the product we added shows as a line item now
	await expect( page ).toMatchElement( '.wc-order-item-name', { text: productName } );
}

/**
 * Creates a basic coupon with the provided coupon amount. Returns the coupon code.
 *
 * @param couponAmount Amount to be applied. Defaults to 5.
 * @param discountType Type of a coupon. Defaults to Fixed cart discount.
 */
const createCoupon = async ( couponAmount = '5', discountType = 'Fixed cart discount' ) => {
	let couponType;
	switch ( discountType ) {
		case "Fixed cart discount":
			couponType = 'fixed_cart';
			break;
		case "Fixed product discount":
			couponType = 'fixed_product';
			break;
		case "Percentage discount":
			couponType = 'percent';
			break;
		default:
			couponType = discountType;
	}

	// Fill in coupon code
	let couponCode = 'code-' + couponType + new Date().getTime().toString();
	const repository = Coupon.restRepository( client );
	await repository.create( {
		code: couponCode,
		discountType: couponType,
		amount: couponAmount,
	});

	return couponCode;
};

/**
 * Adds a shipping zone along with a shipping method.
 *
 * @param zoneName Shipping zone name.
 * @param zoneLocation Shiping zone location. Defaults to country:US. For states use: state:US:CA
 * @param zipCode Shipping zone zip code. Defaults to empty one space.
 * @param zoneMethod Shipping method type. Defaults to flat_rate (use also: free_shipping or local_pickup)
 */
const addShippingZoneAndMethod = async ( zoneName, zoneLocation = 'country:US', zipCode = ' ', zoneMethod = 'flat_rate' ) => {
	await merchant.openNewShipping();

	// Fill shipping zone name
	await page.waitForSelector('input#zone_name');
	await expect(page).toFill('input#zone_name', zoneName);

	// Select shipping zone location
	await expect(page).toSelect('select[name="zone_locations"]', zoneLocation);

	await uiUnblocked();

	// Fill shipping zone postcode if needed otherwise just put empty space
	await page.waitForSelector('a.wc-shipping-zone-postcodes-toggle');
	await expect(page).toClick('a.wc-shipping-zone-postcodes-toggle');
	await expect(page).toFill('#zone_postcodes', zipCode);
	await expect(page).toMatchElement('#zone_postcodes', zipCode);
	await expect(page).toClick('button#submit');

	await uiUnblocked();

	// Add shipping zone method
	await page.waitFor(1000);
	await expect(page).toClick('button.wc-shipping-zone-add-method', {text:'Add shipping method'});
	await page.waitForSelector('.wc-shipping-zone-method-selector');
	await expect(page).toSelect('select[name="add_method_id"]', zoneMethod);
	await expect(page).toClick('button#btn-ok');
	await page.waitForSelector('#zone_locations');

	await uiUnblocked();
};

/**
 * Click the Update button on the order details page.
 *
 * @param noticeText The text that appears in the notice after updating the order.
 * @param waitForSave Optionally wait for auto save.
 */
const clickUpdateOrder = async ( noticeText, waitForSave = false ) => {
	if ( waitForSave ) {
		await page.waitFor( 2000 );
	}

	// Update order
	await expect( page ).toClick( 'button.save_order' );
	await page.waitForSelector( '.updated.notice' );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: noticeText } );
};

/**
 * Delete all email logs in the WP Mail Logging plugin page.
 */
const deleteAllEmailLogs = async () => {
	await merchant.openEmailLog();

	// Make sure we have emails to delete. If we don't, this selector will return null.
	if ( await page.$( '#bulk-action-selector-top' ) !== null ) {
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
	await merchant.openSettings('shipping');

	// Delete existing shipping zones.
	try {
		let zone = await page.$( '.wc-shipping-zone-delete' );
		if ( zone ) {
			// WP action links aren't clickable because they are hidden with a left=-9999 style.
			await page.evaluate(() => {
				document.querySelector('.wc-shipping-zone-name .row-actions')
					.style
					.left = '0';
			});
			while ( zone ) {
				await evalAndClick( '.wc-shipping-zone-delete' );
				await uiUnblocked();
				zone = await page.$( '.wc-shipping-zone-delete' );
			};
		};
	} catch (error) {
		// Prevent an error here causing the test to fail.
	};
};

export {
	completeOnboardingWizard,
	createSimpleProduct,
	createVariableProduct,
	createGroupedProduct,
	createSimpleOrder,
	verifyAndPublish,
	addProductToOrder,
	createCoupon,
	addShippingZoneAndMethod,
	createSimpleProductWithCategory,
	clickUpdateOrder,
	deleteAllEmailLogs,
	deleteAllShippingZones,
	batchCreateOrders,
	createOrder,
};
