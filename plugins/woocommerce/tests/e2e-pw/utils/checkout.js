/**
 * Util helper made for filling shipping details in the block-based checkout
 *
 * @param {Object} page
 * @param {string} country
 * @param {string} firstName
 * @param {string} lastName
 * @param {string} address
 * @param {string} city
 * @param {string} state
 * @param {string} zip
 */
export async function fillShippingCheckoutBlocks(
	page,
	country = 'United States (US)',
	firstName = 'Homer',
	lastName = 'Simpson',
	address = '123 Evergreen Terrace',
	city = 'Springfield',
	state = 'California',
	zip = '97403'
) {
	await page.getByLabel( 'Country/Region' ).click();
	await page.getByRole( 'option', { name: country, exact: true } ).click();
	await page
		.getByRole( 'group', { name: 'Shipping address' } )
		.getByLabel( 'First name' )
		.fill( firstName );
	await page
		.getByRole( 'group', { name: 'Shipping address' } )
		.getByLabel( 'Last name' )
		.fill( lastName );
	await page
		.getByRole( 'group', { name: 'Shipping address' } )
		.getByLabel( 'Address', { exact: true } )
		.fill( address );
	await page
		.getByRole( 'group', { name: 'Shipping address' } )
		.getByLabel( 'City' )
		.fill( city );
	await page.getByLabel( 'State', { exact: true } ).click();
	await page.getByRole( 'option', { name: state, exact: true } ).click();
	await page
		.getByRole( 'group', { name: 'Shipping address' } )
		.getByLabel( 'ZIP Code' )
		.fill( zip );
}

/**
 * Util helper made for filling billing details in the block-based checkout
 *
 * @param {Object} page
 * @param {string} firstName
 * @param {string} lastName
 * @param {string} address
 * @param {string} city
 * @param {string} zip
 */
export async function fillBillingCheckoutBlocks(
	page,
	firstName = 'Mister',
	lastName = 'Burns',
	address = '156th Street',
	city = 'Springfield',
	zip = '98500'
) {
	await page
		.getByRole( 'group', { name: 'Billing address' } )
		.getByLabel( 'First name' )
		.fill( firstName );
	await page
		.getByRole( 'group', { name: 'Billing address' } )
		.getByLabel( 'Last name' )
		.fill( lastName );
	await page
		.getByRole( 'group', { name: 'Billing address' } )
		.getByLabel( 'Address', { exact: true } )
		.fill( address );
	await page
		.getByRole( 'group', { name: 'Billing address' } )
		.getByLabel( 'City' )
		.fill( city );
	await page
		.getByRole( 'group', { name: 'Billing address' } )
		.getByLabel( 'ZIP Code' )
		.fill( zip );
}
