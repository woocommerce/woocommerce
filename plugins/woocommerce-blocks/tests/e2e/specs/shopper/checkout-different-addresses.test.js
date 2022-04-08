/**
 * External dependencies
 */
import {
	merchant,
	openDocumentSettingsSidebar,
	setCheckbox,
	unsetCheckbox,
} from '@woocommerce/e2e-utils';

import {
	visitBlockPage,
	selectBlockByName,
	saveOrPublish,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	shopper,
	preventCompatibilityNotice,
	reactivateCompatibilityNotice,
} from '../../../utils';

import {
	BILLING_DETAILS,
	SHIPPING_DETAILS,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
} from '../../../utils/constants';

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( 'Skipping Checkout tests', () => {} );

let companyCheckboxId = null;

describe( 'Shopper → Checkout → Can have different shipping and billing addresses', () => {
	beforeAll( async () => {
		await preventCompatibilityNotice();
		await merchant.login();
		await visitBlockPage( 'Checkout Block' );
		await openDocumentSettingsSidebar();
		await selectBlockByName(
			'woocommerce/checkout-shipping-address-block'
		);

		// This checkbox ID is unstable, so, we're getting its value from "for" attribute of the label
		const [ companyCheckboxLabel ] = await page.$x(
			`//label[contains(text(), "Company") and contains(@class, "components-toggle-control__label")]`
		);
		companyCheckboxId = await page.evaluate(
			( label ) => `#${ label.getAttribute( 'for' ) }`,
			companyCheckboxLabel
		);

		await setCheckbox( companyCheckboxId );
		await saveOrPublish();
		await shopper.block.emptyCart();
	} );

	afterAll( async () => {
		await shopper.block.emptyCart();
		await visitBlockPage( 'Checkout Block' );
		await openDocumentSettingsSidebar();
		await selectBlockByName(
			'woocommerce/checkout-shipping-address-block'
		);
		await unsetCheckbox( companyCheckboxId );
		await saveOrPublish();
		await merchant.logout();
		await reactivateCompatibilityNotice();
	} );

	it( 'allows customer to have different shipping and billing addresses', async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await shopper.block.goToCheckout();
		await unsetCheckbox( '#checkbox-control-0' );
		await shopper.block.fillShippingDetails( SHIPPING_DETAILS );
		await shopper.block.fillBillingDetails( BILLING_DETAILS );
		await shopper.block.placeOrder();
		await shopper.block.verifyShippingDetails( SHIPPING_DETAILS );
		await shopper.block.verifyBillingDetails( BILLING_DETAILS );
	} );
} );
