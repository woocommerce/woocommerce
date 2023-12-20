/**
 * Util helper made for filling shipping details in the blockbased checkout
 *
 * @param page
 * @param firstName
 * @param lastName
 * @param address
 * @param city
 * @param zip
 */
export async function fillShippingCheckoutBlocks(
	page,
	firstName = 'Homer',
	lastName = 'Simpson',
	address = '123 Evergreen Terrace',
	city = 'Springfield',
	zip = '97403'
) {
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
	await page
		.getByRole( 'group', { name: 'Shipping address' } )
		.getByLabel( 'ZIP Code' )
		.fill( zip );
}

/**
 * Util helper made for filling billing details in the blockbased checkout
 *
 * @param page
 * @param firstName
 * @param lastName
 * @param address
 * @param city
 * @param zip
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
