/**
 * External dependencies
 */
import {
	merchant,
	setCheckbox,
	settingsPageSaveChanges,
	verifyCheckboxIsSet,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getNormalPagePermalink,
	scrollTo,
	shopper,
	visitPostOfType,
} from '../../../utils';

const block = {
	name: 'Checkout',
};

const productPrice = 21.99;
const simpleProductName = 'Woo Single #1';
const singleProductPrice = `$${ productPrice }`;
const twoProductPrice = `$${ productPrice * 2 }`;

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( `${ block.name } Block (frontend)`, () => {
	let productPermalink;

	beforeAll( async () => {
		// prevent CartCheckoutCompatibilityNotice from appearing
		await page.evaluate( () => {
			localStorage.setItem(
				'wc-blocks_dismissed_compatibility_notices',
				'["checkout"]'
			);
		} );
		await merchant.login();

		// Go to general settings page
		await merchant.openSettings( 'general' );

		// Set base location with state CA.
		await expect( page ).toSelect(
			'select[name="woocommerce_default_country"]',
			'United States (US) — California'
		);
		// Sell to all countries
		await expect( page ).toSelect(
			'#woocommerce_allowed_countries',
			'Sell to all countries'
		);
		// Set currency to USD
		await expect( page ).toSelect(
			'#woocommerce_currency',
			'United States (US) dollar ($)'
		);
		// Save
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await Promise.all( [
			expect( page ).toMatchElement( '#message', {
				text: 'Your settings have been saved.',
			} ),
			expect( page ).toMatchElement(
				'select[name="woocommerce_default_country"]',
				{
					text: 'United States (US) — California',
				}
			),
			expect( page ).toMatchElement( '#woocommerce_allowed_countries', {
				text: 'Sell to all countries',
			} ),
			expect( page ).toMatchElement( '#woocommerce_currency', {
				text: 'United States (US) dollar ($)',
			} ),
		] );

		// Enable BACS payment method
		await merchant.openSettings( 'checkout', 'bacs' );
		await setCheckbox( '#woocommerce_bacs_enabled' );
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await verifyCheckboxIsSet( '#woocommerce_bacs_enabled' );

		// Enable COD payment method
		await merchant.openSettings( 'checkout', 'cod' );
		await setCheckbox( '#woocommerce_cod_enabled' );
		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await verifyCheckboxIsSet( '#woocommerce_cod_enabled' );

		// Get product page permalink.
		await visitPostOfType( simpleProductName, 'product' );
		productPermalink = await getNormalPagePermalink();

		await merchant.logout();
	} );

	afterAll( async () => {
		// empty cart from shortcode page
		await shopper.goToCart();
		await shopper.removeFromCart( 'Woo Single #1' );
		await page.evaluate( () => {
			localStorage.removeItem(
				'wc-blocks_dismissed_compatibility_notices'
			);
		} );
	} );

	it( 'should display an empty cart message when cart is empty', async () => {
		await shopper.goToCheckoutBlock();
		const html = await page.content();

		await page.waitForSelector( 'h1', { text: 'Checkout block' } );
		await page.waitForSelector( 'strong', { text: 'Your cart is empty!' } );
	} );

	it( 'allows customer to choose available payment methods', async () => {
		await page.goto( productPermalink );
		await shopper.addToCart();
		await shopper.goToCheckoutBlock();

		await shopper.productIsInCheckoutBlock(
			simpleProductName,
			`1`,
			singleProductPrice
		);
		await page.goBack( { waitUntil: 'networkidle2' } );
		await shopper.addToCart();
		await shopper.goToCheckoutBlock();
		await shopper.productIsInCheckoutBlock(
			simpleProductName,
			`2`,
			twoProductPrice
		);

		await scrollTo( '.wc-block-components-radio-control__input' );

		await expect( page ).toClick(
			'.wc-block-components-payment-method-label',
			{
				text: 'Direct bank transfer',
			}
		);
		await expect( page ).toClick(
			'.wc-block-components-payment-method-label',
			{
				text: 'Cash on delivery',
			}
		);
	} );
} );
