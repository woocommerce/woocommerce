/**
 * Util helper made to fill the Checkout details in the block-based checkout.
 *
 * @param {Object}  page
 * @param {Object}  [details={}]                     - The shipping details object.
 * @param {string}  [details.country='US']           - The first name.
 * @param {string}  [details.firstName='Mister']     - The first name.
 * @param {string}  [details.lastName='Burns']       - The last name.
 * @param {string}  [details.address='156th Street'] - The address.
 * @param {string}  [details.zip='']                 - The ZIP code.
 * @param {string}  [details.city='']                - The city.
 * @param {string}  [details.state='']               - The State.
 * @param {string}  [details.suburb='']              - The Suburb.
 * @param {string}  [details.province='']            - The Province.
 * @param {string}  [details.district='']            - The District.
 * @param {string}  [details.department='']          - The Department.
 * @param {string}  [details.region='']              - The Region.
 * @param {string}  [details.parish='']              - The Parish.
 * @param {string}  [details.county='']              - The Country.
 * @param {string}  [details.prefecture='']          - The Prefecture.
 * @param {string}  [details.municipality='']        - The Municipality.
 * @param {boolean} [details.isPostalCode=false]     - If true, search by 'Postal code' instead of 'Zip Code'.
 */
async function fillCheckoutBlocks( page, details = {}, type = 'shipping' ) {
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

	const label = {
		shipping: 'Shipping address',
		billing: 'Billing address',
	};

	async function setDynamicFieldType( field, addressElement ) {
		const tagName = await field.evaluate( ( el ) =>
			el.tagName.toLowerCase()
		);

		if ( tagName === 'select' ) {
			await field.selectOption( addressElement );
		} else {
			await field.fill( addressElement );
		}
	}

	await page
		.getByRole( 'group', { name: label[ type ] } )
		.getByLabel( 'First name' )
		.fill( firstName );

	await page
		.getByRole( 'group', { name: label[ type ] } )
		.getByLabel( 'Last name' )
		.fill( lastName );

	await page
		.getByRole( 'group', { name: label[ type ] } )
		.getByLabel( 'Address', { exact: true } )
		.fill( address );

	if ( country ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'Country' )
			.selectOption( country );
	}

	if ( city ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'City' )
			.fill( city );
	}

	if ( suburb ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'Suburb' )
			.fill( suburb );
	}

	if ( province ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'Province' )
			.selectOption( province );
	}

	if ( district ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'District' )
			.selectOption( district );
	}

	if ( department ) {
		await setDynamicFieldType(
			await page
				.getByRole( 'group', { name: label[ type ] } )
				.getByLabel( 'Department' ),
			department
		);
	}

	if ( region ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'Region', { exact: true } )
			.selectOption( region );
	}

	if ( parish ) {
		await setDynamicFieldType(
			await page
				.getByRole( 'group', { name: label[ type ] } )
				.getByLabel( 'Parish', { exact: false } ),
			parish
		);
	}

	if ( county ) {
		await setDynamicFieldType(
			await page
				.getByRole( 'group', { name: label[ type ] } )
				.getByLabel( 'County' ),
			county
		);
	}

	if ( prefecture ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'Prefecture' )
			.selectOption( prefecture );
	}

	if ( municipality ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'Municipality' )
			.fill( municipality );
	}

	if ( state ) {
		const stateField = await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( 'State/County', { exact: false } )
			.or(
				await page
					.getByRole( 'group', { name: label[ type ] } )
					.getByLabel( 'State' )
			);

		await setDynamicFieldType( stateField, state );
	}

	if ( zip ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByLabel( isPostalCode ? 'Postal code' : 'ZIP Code' )
			.fill( zip );
	}

	if ( phone ) {
		await page
			.getByRole( 'group', { name: label[ type ] } )
			.getByRole( 'textbox', { name: 'Phone' } )
			.fill( phone );
	}
}

/**
 * Convenience function to fill Shipping Address fields.
 *
 * @param {Object} page
 * @param {*}      shippingDetails See arguments description for `fillCheckoutBlocks`.
 */
export async function fillShippingCheckoutBlocks( page, shippingDetails = {} ) {
	await fillCheckoutBlocks( page, shippingDetails, 'shipping' );
}

/**
 * Convenience function to fill Billing Address fields.
 *
 * @param {Object} page
 * @param {*}      billingDetails See arguments description for `fillCheckoutBlocks`.
 */
export async function fillBillingCheckoutBlocks( page, billingDetails = {} ) {
	await fillCheckoutBlocks( page, billingDetails, 'billing' );
}
