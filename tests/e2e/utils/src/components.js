/**
 * @format
 */

/**
 * Internal dependencies
 */
import { merchant } from './flows';
import { clickTab, uiUnblocked, verifyCheckboxIsUnset, evalAndClick, selectOptionInSelect2 } from './page-utils';
import factories from './factories';

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );
const simpleProductPrice = config.has('products.simple.price') ? config.get('products.simple.price') : '9.99';

const verifyAndPublish = async () => {
	// Wait for auto save
	await page.waitFor( 2000 );

	// Publish product
	await expect( page ).toClick( '#publish' );
	await page.waitForSelector( '.updated.notice' );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: 'Product published.' } );
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

	// Wait for usage tracking pop-up window to appear
	await page.waitForSelector( '.components-modal__header-heading' );
	await expect( page ).toMatchElement(
		'.components-modal__header-heading', { text: 'Build a better WooCommerce' }
	);

	// Query for "Continue" buttons
	const continueButtons = await page.$$( 'button.is-primary' );
	expect( continueButtons ).toHaveLength( 2 );

	await Promise.all( [
		// Click on "Continue" button of the usage pop-up window to move to the next step
		continueButtons[1].click(),

		// Wait for "In which industry does the store operate?" section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

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
	await evalAndClick( '.components-checkbox-control__input' );
	await waitAndClickPrimary();

	// Theme section
	await waitAndClickPrimary();

	// End of onboarding wizard

	// Wait for homescreen welcome modal to appear
	await page.waitForSelector( '.woocommerce__welcome-modal__page-content__header' );
	await expect( page ).toMatchElement(
		'.woocommerce__welcome-modal__page-content__header', { text: 'Welcome to your WooCommerce store\’s online HQ!' }
	);

	// Wait for "Next" button to become active
	await page.waitForSelector( 'button.components-guide__forward-button' );
	// Click on "Next" button to move to the next step
	await page.click( 'button.components-guide__forward-button' );

	// Wait for "Next" button to become active
	await page.waitForSelector( 'button.components-guide__forward-button' );
	// Click on "Next" button to move to the next step
	await page.click( 'button.components-guide__forward-button' );

	// Wait for "Let's go" button to become active
	await page.waitForSelector( 'button.components-guide__finish-button' );
	// Click on "Let's go" button to move to the next step
	await page.click( 'button.components-guide__finish-button' );
};

/**
 * Create simple product.
 */
const createSimpleProduct = async () => {
	const product = await factories.products.simple.create( {
		name: simpleProductName,
		regularPrice: simpleProductPrice
	} );
	return product.id;
} ;

/**
 * Create variable product.
 */
const createVariableProduct = async () => {
	// Go to "add product" page
	await merchant.openNewProduct();

	// Make sure we're on the add product page
	await expect( page.title() ).resolves.toMatch( 'Add new product' );

	// Set product data
	await expect( page ).toFill( '#title', 'Variable Product with Three Variations' );
	await expect( page ).toSelect( '#product-type', 'Variable product' );

	// Create attributes for variations
	await clickTab( 'Attributes' );
	await expect( page ).toSelect( 'select[name="attribute_taxonomy"]', 'Custom product attribute' );

	for ( let i = 0; i < 3; i++ ) {
		await expect( page ).toClick( 'button.add_attribute', { text: 'Add' } );
		// Wait for attribute form to load
		await uiUnblocked();

		await page.focus( `input[name="attribute_names[${ i }]"]` );
		await expect( page ).toFill( `input[name="attribute_names[${ i }]"]`, 'attr #' + ( i + 1 ) );
		await expect( page ).toFill( `textarea[name="attribute_values[${ i }]"]`, 'val1 | val2' );
		await expect( page ).toClick( `input[name="attribute_variation[${ i }]"]` );
	}

	await expect( page ).toClick( 'button', { text: 'Save attributes' } );

	// Wait for attribute form to save (triggers 2 UI blocks)
	await uiUnblocked();
	await page.waitFor( 1000 );
	await uiUnblocked();

	// Create variations from attributes
	await clickTab( 'Variations' );
	await page.waitForSelector( 'select.variation_actions:not([disabled])' );
	await page.focus( 'select.variation_actions' );
	await expect( page ).toSelect( 'select.variation_actions', 'Create variations from all attributes' );

	const firstDialog = await expect( page ).toDisplayDialog( async () => {
		// Using this technique since toClick() isn't working.
		// See: https://github.com/GoogleChrome/puppeteer/issues/1805#issuecomment-464802876
		page.$eval( 'a.do_variation_action', elem => elem.click() );

	} );

	expect( firstDialog.message() ).toMatch( 'Are you sure you want to link all variations?' );

	const secondDialog = await expect( page ).toDisplayDialog( async () => {
		await firstDialog.accept();
	} );

	expect( secondDialog.message() ).toMatch( '8 variations added' );
	await secondDialog.dismiss();

	// Set some variation data
	await uiUnblocked();
	await uiUnblocked();

	await page.waitForSelector( '.woocommerce_variation .handlediv' );

	// Verify that variations were created
	await Promise.all( [
		expect( page ).toMatchElement( 'select[name="attribute_attr-1[0]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[0]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[0]"]', { text: 'val1' } ),

		expect( page ).toMatchElement( 'select[name="attribute_attr-1[1]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[1]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[1]"]', { text: 'val2' } ),

		expect( page ).toMatchElement( 'select[name="attribute_attr-1[2]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[2]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[2]"]', { text: 'val1' } ),

		expect( page ).toMatchElement( 'select[name="attribute_attr-1[3]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[3]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[3]"]', { text: 'val2' } ),

		expect( page ).toMatchElement( 'select[name="attribute_attr-1[4]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[4]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[4]"]', { text: 'val1' } ),

		expect( page ).toMatchElement( 'select[name="attribute_attr-1[5]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[5]"]', { text: 'val1' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[5]"]', { text: 'val2' } ),

		expect( page ).toMatchElement( 'select[name="attribute_attr-1[6]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[6]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[6]"]', { text: 'val1' } ),

		expect( page ).toMatchElement( 'select[name="attribute_attr-1[7]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-2[7]"]', { text: 'val2' } ),
		expect( page ).toMatchElement( 'select[name="attribute_attr-3[7]"]', { text: 'val2' } ),
	] );

	await expect( page ).toClick( '.woocommerce_variation:nth-of-type(2) .handlediv' );
	await page.waitFor( 2000 );
	await page.focus( 'input[name="variable_is_virtual[0]"]' );
	await expect( page ).toClick( 'input[name="variable_is_virtual[0]"]' );
	await expect( page ).toFill( 'input[name="variable_regular_price[0]"]', '9.99' );

	await expect( page ).toClick( '.woocommerce_variation:nth-of-type(3) .handlediv' );
	await page.waitFor( 2000 );
	await page.focus( 'input[name="variable_is_virtual[1]"]' );
	await expect( page ).toClick( 'input[name="variable_is_virtual[1]"]' );
	await expect( page ).toFill( 'input[name="variable_regular_price[1]"]', '11.99' );

	await expect( page ).toClick( '.woocommerce_variation:nth-of-type(4) .handlediv' );
	await page.waitFor( 2000 );
	await page.focus( 'input[name="variable_manage_stock[2]"]' );
	await expect( page ).toClick( 'input[name="variable_manage_stock[2]"]' );
	await expect( page ).toFill( 'input[name="variable_regular_price[2]"]', '20' );
	await expect( page ).toFill( 'input[name="variable_weight[2]"]', '200' );
	await expect( page ).toFill( 'input[name="variable_length[2]"]', '10' );
	await expect( page ).toFill( 'input[name="variable_width[2]"]', '20' );
	await expect( page ).toFill( 'input[name="variable_height[2]"]', '15' );

	await page.focus( 'button.save-variation-changes' );
	await expect( page ).toClick( 'button.save-variation-changes', { text: 'Save changes' } );

	await verifyAndPublish();

	const variablePostId = await page.$( '#post_ID' );
	let variablePostIdValue = ( await ( await variablePostId.getProperty( 'value' ) ).jsonValue() );
	return variablePostIdValue;
};

/**
 * Create grouped product.
 */
const createGroupedProduct = async () => {
	// Create two products to be linked in a grouped product after
	await factories.products.simple.create( {
		name: simpleProductName + ' 1',
		regularPrice: simpleProductPrice
	} );
	await factories.products.simple.create( {
		name: simpleProductName + ' 2',
		regularPrice: simpleProductPrice
	} );

	// Go to "add product" page
	await merchant.openNewProduct();

	// Make sure we're on the add product page
	await expect( page.title() ).resolves.toMatch( 'Add new product' );

	// Set product data and save the product
	await expect( page ).toFill( '#title', 'Grouped Product' );
	await expect( page ).toSelect( '#product-type', 'Grouped product' );
	await clickTab( 'Linked Products' );
	await selectOptionInSelect2( simpleProductName + ' 1' );
	await selectOptionInSelect2( simpleProductName + ' 2' );
	await verifyAndPublish();

	// Get product ID
	const groupedPostId = await page.$( '#post_ID' );
	let groupedPostIdValue = ( await ( await groupedPostId.getProperty( 'value' ) ).jsonValue() );
	return groupedPostIdValue;
}

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
	await expect( page ).toFill( '#wc-backbone-modal-dialog + .select2-container .select2-search__field', productName );
	await expect( page ).toClick( 'li[aria-selected="true"]' );
	await page.click( '.wc-backbone-modal-content #btn-ok' );

	await uiUnblocked();

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
	await merchant.openNewCoupon();

	// Fill in coupon code
	let couponCode = 'Code-' + discountType + new Date().getTime().toString();
	await expect(page).toFill( '#title', couponCode );

	// Set general coupon data
	await clickTab( 'General' );
	await expect(page).toSelect( '#discount_type', discountType );
	await expect(page).toFill( '#coupon_amount', couponAmount );

	// Publish coupon
	await expect( page ).toClick( '#publish' );
	await page.waitForSelector( '.updated.notice' );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: 'Coupon updated.' } );

	return couponCode;
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
};
