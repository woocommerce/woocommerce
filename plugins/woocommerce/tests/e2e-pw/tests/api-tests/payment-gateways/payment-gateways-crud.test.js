const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );

test.describe( 'Payment Gateways API tests', () => {
	test( 'can view all payment gateways', async ( { request } ) => {
		// call API to retrieve the payment gateways
		const response = await request.get( '/wp-json/wc/v3/payment_gateways' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		const localPickupKey =
			// eslint-disable-next-line playwright/no-conditional-in-test
			process.env.BASE_URL &&
			! process.env.BASE_URL.includes( 'localhost' )
				? 'pickup_location'
				: 'local_pickup';
		console.log( 'localPickupKey=', localPickupKey );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					id: 'bacs',
					title: 'Direct bank transfer',
					description:
						'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will not be shipped until the funds have cleared in our account.',
					order: '',
					enabled: false,
					method_title: 'Direct bank transfer',
					method_description:
						'Take payments in person via BACS. More commonly known as direct bank/wire transfer.',
					method_supports: [ 'products' ],
					settings: {
						title: {
							id: 'title',
							label: 'Title',
							description:
								'This controls the title which the user sees during checkout.',
							type: 'safe_text',
							value: 'Direct bank transfer',
							default: 'Direct bank transfer',
							tip: 'This controls the title which the user sees during checkout.',
							placeholder: '',
						},
						instructions: {
							id: 'instructions',
							label: 'Instructions',
							description:
								'Instructions that will be added to the thank you page and emails.',
							type: 'textarea',
							value: '',
							default: '',
							tip: 'Instructions that will be added to the thank you page and emails.',
							placeholder: '',
						},
					},
				} ),

				expect.objectContaining( {
					id: 'cheque',
					title: 'Check payments',
					description:
						'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.',
					order: '',
					enabled: false,
					method_title: 'Check payments',
					method_description:
						'Take payments in person via checks. This offline gateway can also be useful to test purchases.',
					method_supports: [ 'products' ],
					settings: {
						title: {
							id: 'title',
							label: 'Title',
							description:
								'This controls the title which the user sees during checkout.',
							type: 'safe_text',
							value: 'Check payments',
							default: 'Check payments',
							tip: 'This controls the title which the user sees during checkout.',
							placeholder: '',
						},
						instructions: {
							id: 'instructions',
							label: 'Instructions',
							description:
								'Instructions that will be added to the thank you page and emails.',
							type: 'textarea',
							value: '',
							default: '',
							tip: 'Instructions that will be added to the thank you page and emails.',
							placeholder: '',
						},
					},
				} ),

				expect.objectContaining( {
					id: 'cod',
					title: 'Cash on delivery',
					description: 'Pay with cash upon delivery.',
					order: '',
					enabled: false,
					method_title: 'Cash on delivery',
					method_description:
						'Have your customers pay with cash (or by other means) upon delivery.',
					method_supports: [ 'products' ],
					settings: {
						title: {
							id: 'title',
							label: 'Title',
							description:
								'Payment method description that the customer will see on your checkout.',
							type: 'safe_text',
							value: 'Cash on delivery',
							default: 'Cash on delivery',
							tip: 'Payment method description that the customer will see on your checkout.',
							placeholder: '',
						},
						instructions: {
							id: 'instructions',
							label: 'Instructions',
							description:
								'Instructions that will be added to the thank you page.',
							type: 'textarea',
							value: 'Pay with cash upon delivery.',
							default: 'Pay with cash upon delivery.',
							tip: 'Instructions that will be added to the thank you page.',
							placeholder: '',
						},
						enable_for_methods: {
							id: 'enable_for_methods',
							label: 'Enable for shipping methods',
							description:
								'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.',
							type: 'multiselect',
							value: '',
							default: '',
							tip: 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.',
							placeholder: '',
							options: expect.objectContaining( {
								'Flat rate': {
									flat_rate:
										'Any &quot;Flat rate&quot; method',
								},
								'Free shipping': {
									free_shipping:
										'Any &quot;Free shipping&quot; method',
								},
								'Local pickup': expect.objectContaining( {
									[ localPickupKey ]:
										'Any &quot;Local pickup&quot; method',
								} ),
							} ),
						},
						enable_for_virtual: {
							id: 'enable_for_virtual',
							label: 'Accept COD if the order is virtual',
							description: '',
							type: 'checkbox',
							value: 'yes',
							default: 'yes',
							tip: '',
							placeholder: '',
						},
					},
				} ),
			] )
		);
	} );

	test( 'can view a payment gateway', async ( { request } ) => {
		// call API to retrieve a single payment gateway
		const response = await request.get(
			'/wp-json/wc/v3/payment_gateways/bacs'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( false );

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				id: 'bacs',
				title: 'Direct bank transfer',
				description:
					'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will not be shipped until the funds have cleared in our account.',
				order: '',
				enabled: false,
				method_title: 'Direct bank transfer',
				method_description:
					'Take payments in person via BACS. More commonly known as direct bank/wire transfer.',
				method_supports: [ 'products' ],
				settings: {
					title: {
						id: 'title',
						label: 'Title',
						description:
							'This controls the title which the user sees during checkout.',
						type: 'safe_text',
						value: 'Direct bank transfer',
						default: 'Direct bank transfer',
						tip: 'This controls the title which the user sees during checkout.',
						placeholder: '',
					},
					instructions: {
						id: 'instructions',
						label: 'Instructions',
						description:
							'Instructions that will be added to the thank you page and emails.',
						type: 'textarea',
						value: '',
						default: '',
						tip: 'Instructions that will be added to the thank you page and emails.',
						placeholder: '',
					},
				},
			} )
		);
	} );

	test( 'can update a payment gateway', async ( { request } ) => {
		// call API to update a payment gateway
		const response = await request.put(
			'/wp-json/wc/v3/payment_gateways/bacs',
			{
				data: {
					enabled: true,
				},
			}
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				enabled: true,
			} )
		);

		// reset payment gateway setting
		await request.put( '/wp-json/wc/v3/payment_gateways/bacs', {
			data: {
				enabled: false,
			},
		} );
	} );
} );
