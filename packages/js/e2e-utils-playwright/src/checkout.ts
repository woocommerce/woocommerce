/**
 * External dependencies
 */
import type { Page, Locator } from '@playwright/test';

/**
 * Internal dependencies
 */
import type { CheckoutDetails, AddressType } from './types';

// Re-export types for consumers
export type { CheckoutDetails } from './types';

const addressLabels: Record< AddressType, string > = {
	shipping: 'Shipping address',
	billing: 'Billing address',
};

/**
 * Sets a field value based on its element type (select or input).
 *
 * @param field - The field locator
 * @param value - The value to set
 */
async function setDynamicFieldType( field: Locator, value: string ) {
	const tagName = await field.evaluate( ( el ) => el.tagName.toLowerCase() );

	if ( tagName === 'select' ) {
		await field.selectOption( value );
	} else {
		await field.fill( value );
	}
}

/**
 * Util helper made to fill the Checkout details in the block-based checkout.
 *
 * @param page    - Playwright page object
 * @param details - The checkout details object
 * @param type    - The address type ('shipping' or 'billing')
 */
async function fillCheckoutBlocks(
	page: Page,
	details: CheckoutDetails = {},
	type: AddressType = 'shipping'
) {
	const {
		country = '',
		firstName = '',
		lastName = '',
		address = '',
		zip = '',
		city = '',
		state = '',
		suburb = '',
		province = '',
		district = '',
		department = '',
		region = '',
		parish = '',
		county = '',
		prefecture = '',
		municipality = '',
		phone = '',
		isPostalCode = false,
	} = details;

	const label = addressLabels[ type ];

	await page
		.getByRole( 'group', { name: label } )
		.getByLabel( 'First name' )
		.fill( firstName );

	await page
		.getByRole( 'group', { name: label } )
		.getByLabel( 'Last name' )
		.fill( lastName );

	await page
		.getByRole( 'group', { name: label } )
		.getByLabel( 'Address', { exact: true } )
		.fill( address );

	if ( country ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'Country' )
			.selectOption( country );
	}

	if ( city ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'City' )
			.fill( city );
	}

	if ( suburb ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'Suburb' )
			.fill( suburb );
	}

	if ( province ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'Province' )
			.selectOption( province );
	}

	if ( district ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'District' )
			.selectOption( district );
	}

	if ( department ) {
		await setDynamicFieldType(
			page
				.getByRole( 'group', { name: label } )
				.getByLabel( 'Department' ),
			department
		);
	}

	if ( region ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'Region', { exact: true } )
			.selectOption( region );
	}

	if ( parish ) {
		await setDynamicFieldType(
			page
				.getByRole( 'group', { name: label } )
				.getByLabel( 'Parish', { exact: false } ),
			parish
		);
	}

	if ( county ) {
		await setDynamicFieldType(
			page.getByRole( 'group', { name: label } ).getByLabel( 'County' ),
			county
		);
	}

	if ( prefecture ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'Prefecture' )
			.selectOption( prefecture );
	}

	if ( municipality ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'Municipality' )
			.fill( municipality );
	}

	if ( state ) {
		const stateField = page
			.getByRole( 'group', { name: label } )
			.getByLabel( 'State/County', { exact: false } )
			.or(
				page.getByRole( 'group', { name: label } ).getByLabel( 'State' )
			);

		await setDynamicFieldType( stateField, state );
	}

	if ( zip ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByLabel( isPostalCode ? 'Postal code' : 'ZIP Code' )
			.fill( zip );
	}

	if ( phone ) {
		await page
			.getByRole( 'group', { name: label } )
			.getByRole( 'textbox', { name: 'Phone' } )
			.fill( phone );
	}
}

/**
 * Convenience function to fill Shipping Address fields.
 *
 * @param page            - Playwright page object
 * @param shippingDetails - See CheckoutDetails type for available fields
 */
export async function fillShippingCheckoutBlocks(
	page: Page,
	shippingDetails: CheckoutDetails = {}
): Promise< void > {
	await fillCheckoutBlocks( page, shippingDetails, 'shipping' );
}

/**
 * Convenience function to fill Billing Address fields.
 *
 * @param page           - Playwright page object
 * @param billingDetails - See CheckoutDetails type for available fields
 */
export async function fillBillingCheckoutBlocks(
	page: Page,
	billingDetails: CheckoutDetails = {}
): Promise< void > {
	await fillCheckoutBlocks( page, billingDetails, 'billing' );
}
