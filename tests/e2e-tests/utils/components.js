/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from './flows';
import { clickTab, uiUnblocked, verifyCheckboxIsUnset } from './index';

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

const verifyAndPublish = async () => {
	// Wait for auto save
	await page.waitFor( 2000 );

	// Publish product
	await expect( page ).toClick( '#publish' );
	await page.waitForSelector( '.updated.notice' );
	// waitForSelector is not enough here...To-Do: think of a better solution
	await page.waitFor( 2000 );

	// Verify
	await expect( page ).toMatchElement( '.updated.notice', { text: 'Product published.' } );
};

/**
 * Complete old setup wizard.
 */
const completeOldSetupWizard = async () => {
	// Fill out store setup section details
	// Select country where the store is located
	await expect( page ).toSelect( 'select[name="store_country"]', config.get( 'addresses.admin.store.country' ) );
	// Fill store's address - first line
	await expect( page ).toFill( '#store_address', config.get( 'addresses.admin.store.addressfirstline' ) );

	// Fill store's address - second line
	await expect( page ).toFill( '#store_address_2', config.get( 'addresses.admin.store.addresssecondline' ) );

	// Fill the city where the store is located
	await expect( page ).toFill( '#store_city', config.get( 'addresses.admin.store.city' ) );

	// Select the state where the store is located
	await expect( page ).toSelect( 'select[name="store_state"]', config.get( 'addresses.admin.store.state') );

	// Fill postcode of the store
	await expect( page ).toFill( '#store_postcode', config.get( 'addresses.admin.store.postcode' ) );

	// Select currency and type of products to sell details
	await expect( page ).toSelect( 'select[name="currency_code"]', '\n' +
		'\t\t\t\t\t\tUnited States (US) dollar ($ USD)\t\t\t\t\t' );
	await expect( page ).toSelect( 'select[name="product_type"]', 'I plan to sell both physical and digital products' );

	// Verify that checkbox next to "I will also be selling products or services in person." is not selected
	await verifyCheckboxIsUnset( '#woocommerce_sell_in_person' );

	// Click on "Let's go!" button to move to the next step
	await page.$eval( 'button[name=save_step]', elem => elem.click() );

	// Wait for usage tracking pop-up window to appear
	await page.waitForSelector( '#wc-backbone-modal-dialog' );
	await expect( page ).toMatchElement(
		'.wc-backbone-modal-header', { text: 'Help improve WooCommerce with usage tracking' }
	);

	await page.waitForSelector( '#wc_tracker_checkbox_dialog' );

	// Verify that checkbox next to "Enable usage tracking and help improve WooCommerce" is not selected
	await verifyCheckboxIsUnset( '#wc_tracker_checkbox_dialog' );

	await Promise.all( [
		// Click on "Continue" button to move to the next step
		page.$eval( '#wc_tracker_submit', elem => elem.click() ),

		// Wait for the Payment section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

	// Fill out payment section details
	// Turn off Stripe account toggle
	await page.click( '.wc-wizard-service-toggle' );

	await Promise.all( [
		// Click on "Continue" button to move to the next step
		page.click( 'button[name=save_step]', { text: 'Continue' } ),

		// Wait for the Shipping section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

	// Fill out shipping section details
	// Turn off WooCommerce Shipping option
	await page.$eval( '#wc_recommended_woocommerce_services', elem => elem.click() );

	await page.waitForSelector( 'select[name="shipping_zones[domestic][method]"]' );
	await page.waitForSelector( 'select[name="shipping_zones[intl][method]"]' );

	// Select Flat Rate shipping method for domestic shipping zone
	await page.evaluate( () => {
		document.querySelector( 'select[name="shipping_zones[domestic][method]"] > option:nth-child(1)' ).selected = true;
		let element = document.querySelector( 'select[name="shipping_zones[domestic][method]"]' );
		let event = new Event( 'change', { bubbles: true } );
		event.simulated = true;
		element.dispatchEvent( event );
	} );

	await page.$eval( 'input[name="shipping_zones[domestic][flat_rate][cost]"]', e => e.setAttribute( 'value', '10.00' ) );

	// Select Flat Rate shipping method for the rest of the world shipping zone
	await page.evaluate( () => {
		document.querySelector( 'select[name="shipping_zones[intl][method]"] > option:nth-child(1)' ).selected = true;
		let element = document.querySelector( 'select[name="shipping_zones[intl][method]"]' );
		let event = new Event( 'change', { bubbles: true } );
		event.simulated = true;
		element.dispatchEvent( event );
	} );

	await page.$eval( 'input[name="shipping_zones[intl][flat_rate][cost]"]', e => e.setAttribute( 'value', '20.00' ) );

	// Select product weight and product dimensions options
	await expect( page ).toSelect( 'select[name="weight_unit"]', 'Pounds' );
	await expect( page ).toSelect( 'select[name="dimension_unit"]', 'Inches' );

	await Promise.all( [
		// Click on "Continue" button to move to the next step
		page.click( 'button[name=save_step]', { text: 'Continue' } ),

		// Wait for the Recommended section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

	// Fill out recommended section details
	// Turn off Storefront Theme option
	await page.waitForSelector( '#wc_recommended_storefront_theme', { visible: true } );
	await page.$eval( '#wc_recommended_storefront_theme', elem => elem.click() );

	// Turn off Automated Taxes option
	await page.waitForSelector( '#wc_recommended_automated_taxes', { visible: true } );
	await page.$eval( '#wc_recommended_automated_taxes', elem => elem.click() );

	// Turn off Mailchimp option
	await page.waitForSelector( '#wc_recommended_mailchimp', { visible: true } );
	await page.$eval( '#wc_recommended_mailchimp', elem => elem.click() );

	// Turn off Facebook option
	await page.waitForSelector( '#wc_recommended_facebook', { visible: true } );
	await page.$eval( '#wc_recommended_facebook', elem => elem.click() );

	await Promise.all( [
		// Click on "Continue" button to move to the next step
		page.click( 'button[name=save_step]', { text: 'Continue' } ),

		// Wait for the Jetpack section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

	// Skip activate Jetpack section
	// Click on "Skip this step" in order to skip Jetpack installation
	await page.click( '.wc-setup-footer-links' );

	// Finish Setup Wizard - Ready! section
	// Visit Dashboard
	await StoreOwnerFlow.openDashboard();
} ;

/**
 * Create simple product.
 */
const createSimpleProduct = async () => {
	// Go to "add product" page
	await StoreOwnerFlow.openNewProduct();

	// Make sure we're on the add product page
	await expect( page ).toMatchElement( '.wp-heading-inline', { text: 'Add new product' } );

	// Set product data
	await expect( page ).toFill( '#title', simpleProductName );
	await clickTab( 'General' );
	await expect( page ).toFill( '#_regular_price', '9.99' );

	await verifyAndPublish();

	const simplePostId = await page.$( '#post_ID' );
	let simplePostIdValue = ( await ( await simplePostId.getProperty( 'value' ) ).jsonValue() );
	return simplePostIdValue;
} ;

/**
 * Create variable product.
 */
const createVariableProduct = async () => {
	// Go to "add product" page
	await StoreOwnerFlow.openNewProduct();

	// Make sure we're on the add product page
	await expect( page ).toMatchElement( '.wp-heading-inline', { text: 'Add new product' } );

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
	await uiUnblocked();

	// Create variations from attributes
	await clickTab( 'Variations' );
	await page.waitForSelector( 'select.variation_actions:not([disabled])' );
	await page.focus( 'select.variation_actions' );
	await expect( page ).toSelect( 'select.variation_actions', 'Create variations from all attributes' );

	// Close all dialogues that pop up
	page.on( 'dialog', async dialog => {
		await dialog.accept();
	} );

	// Normally clicking a link would be like this:
	// 		await page.waitForSelector('a.do_variation_action');
	// 		await page.click('a.do_variation_action');
	// However this doesn't work. Using another technique:
	// See: https://github.com/GoogleChrome/puppeteer/issues/1805#issuecomment-464802876
	await page.$eval( 'a.do_variation_action', elem => elem.click() );

	// Set variation data (2 UI blocks)
	await uiUnblocked();
	await uiUnblocked();

	// 'Variations price is not set...' notice should be displayed
	await page.waitForSelector( '.woocommerce-notice-invalid-variation' );

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
	] )
		.catch( e => console.error( e ) )
		.finally( () => console.log( 'Promise executed: variations were created' ) );

	await page.waitFor( 2000 ); // waitForSelector fails here...To-Do
	await expect( page ).toClick( '.woocommerce_variation:nth-of-type(2) .handlediv' );

	await page.waitFor( 2000 ); // wait for dropdown details to load...To-Do
	await expect( page ).toClick( 'input[name="variable_is_virtual[0]"]' );
	await expect( page ).toFill( 'input[name="variable_regular_price[0]"]', '9.99' );

	await expect( page ).toClick( '.woocommerce_variation:nth-of-type(3) .handlediv' );

	await page.waitFor( 2000 ); // wait for dropdown details to load...To-Do
	await expect( page ).toClick( 'input[name="variable_is_virtual[1]"]' );
	await expect( page ).toFill( 'input[name="variable_regular_price[1]"]', '11.99' );

	await expect( page ).toClick( '.woocommerce_variation:nth-of-type(4) .handlediv' );

	await page.waitFor( 2000 ); // wait for dropdown details to load...To-Do
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

export {
	completeOldSetupWizard,
	createSimpleProduct,
	createVariableProduct,
	verifyAndPublish,
};
