/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from './flows';
import modelRegistry from './factories';
import { SimpleProduct } from '@woocommerce/model-factories';
import { clickTab, uiUnblocked, verifyCheckboxIsUnset } from './page-utils';

const config = require( 'config' );
const simpleProductName = config.get( 'products.simple.name' );

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
	expect( industryCheckboxes ).toHaveLength( 9 );

	// Select all industries including "Other"
	for ( let i = 0; i < 9; i++ ) {
		await industryCheckboxes[i].click();
	}

	// Fill "Other" industry
	await expect( page ).toFill( '.components-text-control__input', config.get( 'onboardingwizard.industry' ) );

	// Wait for "Continue" button to become active
	await page.waitForSelector( 'button.is-primary:not(:disabled)' );

	await Promise.all( [
		// Click on "Continue" button to move to the next step
		page.click( 'button.is-primary' ),

		// Wait for "What type of products will be listed?" section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

	// Product types section

	// Query for the product types checkboxes
	const productTypesCheckboxes = await page.$$( '.components-checkbox-control__input' );
	expect( productTypesCheckboxes ).toHaveLength( 7 );

	// Select Physical and Downloadable products
	for ( let i = 1; i < 2; i++ ) {
		await productTypesCheckboxes[i].click();
	}

	// Wait for "Continue" button to become active
	await page.waitForSelector( 'button.is-primary:not(:disabled)' );

	await Promise.all( [
		// Click on "Continue" button to move to the next step
		page.click( 'button.is-primary' ),

		// Wait for "Tell us about your business" section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

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

	// Query for the extensions toggles
	const extensionsToggles = await page.$$( '.components-form-toggle__input' );
	expect( extensionsToggles ).toHaveLength( 3 );

	// Disable download of the 3 extensions
	for ( let i = 0; i < 3; i++ ) {
		await extensionsToggles[i].click();
	}

	// Wait for "Continue" button to become active
	await page.waitForSelector( 'button.is-primary:not(:disabled)' );

	await Promise.all( [
		// Click on "Continue" button to move to the next step
		page.click( 'button.is-primary' ),

		// Wait for "Theme" section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

	// Theme section

	// Wait for "Continue with my active theme" button to become active
	await page.waitForSelector( 'button.is-primary:not(:disabled)' );

	await Promise.all( [
		// Click on "Continue with my active theme" button to move to the next step
		page.click( 'button.is-primary' ),

		// Wait for "Enhance your store with WooCommerce Services" section to load
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );

	// Benefits section

	// Wait for Benefits section to appear
	await page.waitForSelector( '.woocommerce-profile-wizard__step-header' );

	// Wait for "No thanks" button to become active
	await page.waitForSelector( 'button.is-secondary:not(:disabled)' );
	// Click on "No thanks" button to move to the next step
	await page.click( 'button.is-secondary' );

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
	const product = await modelRegistry.getFactory( SimpleProduct ).create( {
		name: simpleProductName,
		regularPrice: '9.99'
	} );
	return product.id;
} ;

/**
 * Create variable product.
 */
const createVariableProduct = async () => {
	// Go to "add product" page
	await StoreOwnerFlow.openNewProduct();

	// Make sure we're on the add order page
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

export {
	completeOldSetupWizard,
	completeOnboardingWizard,
	createSimpleProduct,
	createVariableProduct,
	verifyAndPublish,
};
