const {
	test,
	expect,
	request: apiRequest,
	tags,
} = require( '../../../fixtures/api-tests-fixtures' );
const { setOption } = require( '../../../utils/options' );

const { BASE_URL } = process.env;

const {
	countries,
	currencies,
	stateOptions,
} = require( '../../../data/settings' );

const enableEmailImprovementsFeature = async () => {
	await setOption(
		apiRequest,
		BASE_URL,
		'woocommerce_feature_email_improvements_enabled',
		'yes'
	);
};

const disableEmailImprovementsFeature = async () => {
	await setOption(
		apiRequest,
		BASE_URL,
		'woocommerce_feature_email_improvements_enabled',
		'no'
	);
};

test.describe( 'Settings API tests: CRUD', () => {
	test.describe( 'List all settings groups', () => {
		test.beforeAll( disableEmailImprovementsFeature );
		test( 'can retrieve all settings groups', async ( { request } ) => {
			// call API to retrieve all settings groups
			const response = await request.get( './wp-json/wc/v3/settings' );
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'wc_admin',
						label: 'WooCommerce Admin',
						description:
							'Settings for WooCommerce admin reporting.',
						parent_id: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'general',
						label: 'General',
						description: '',
						parent_id: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'products',
						label: 'Products',
						description: '',
						parent_id: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'tax',
						label: 'Tax',
						description: '',
						parent_id: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'shipping',
						label: 'Shipping',
						description: '',
						parent_id: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'checkout',
						label: 'Payments',
						description: '',
						parent_id: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'account',
						label: 'Accounts &amp; Privacy',
						description: '',
						parent_id: '',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email',
						label: 'Emails',
						description: '',
						parent_id: '',
						sub_groups: expect.arrayContaining( [
							'email_new_order',
						] ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'integration',
						label: 'Integration',
						description: '',
						parent_id: '',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'advanced',
						label: 'Advanced',
						description: '',
						parent_id: '',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_new_order',
						label: 'New order',
						description:
							'New order emails are sent to chosen recipient(s) when a new order is received.',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_cancelled_order',
						label: 'Cancelled order',
						description:
							'Cancelled order emails are sent to chosen recipient(s) when orders have been marked cancelled (if they were previously processing or on-hold).',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_failed_order',
						label: 'Failed order',
						description:
							'Failed order emails are sent to chosen recipient(s) when orders have been marked failed (if they were previously pending or on-hold).',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_on_hold_order',
						label: 'Order on-hold',
						description:
							'This is an order notification sent to customers containing order details after an order is placed on-hold from Pending, Cancelled or Failed order status.',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_processing_order',
						label: 'Processing order',
						description:
							'This is an order notification sent to customers containing order details after payment.',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_completed_order',
						label: 'Completed order',
						description:
							'Order complete emails are sent to customers when their orders are marked completed and usually indicate that their orders have been shipped.',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_refunded_order',
						label: 'Refunded order',
						description:
							'Order refunded emails are sent to customers when their orders are refunded.',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_invoice',
						label: 'Order details',
						description:
							'Order detail emails can be sent to customers containing their order information and payment links.',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_note',
						label: 'Customer note',
						description:
							'Customer note emails are sent when you add a note to an order.',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_reset_password',
						label: 'Reset password',
						description:
							'Send an email to customers notifying them that their password has been reset',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_customer_new_account',
						label: 'New account',
						description:
							'Send an email to customers notifying them that they have created an account',
						parent_id: 'email',
						sub_groups: expect.arrayContaining( [] ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all settings options', () => {
		test( 'can retrieve all general settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/general'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_store_address',
						label: 'Address line 1',
						description:
							'The street address for your business location.',
						type: 'text',
						default: '',
						tip: 'The street address for your business location.',
						value: expect.any( String ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_store_address_2',
						label: 'Address line 2',
						description:
							'An additional, optional address line for your business location.',
						type: 'text',
						default: '',
						tip: 'An additional, optional address line for your business location.',
						value: '',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_store_city',
						label: 'City',
						description:
							'The city in which your business is located.',
						type: 'text',
						default: '',
						tip: 'The city in which your business is located.',
						value: expect.any( String ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_default_country',
						label: 'Country / State',
						description:
							'The country and state or province, if any, in which your business is located.',
						type: 'select',
						default: 'US:CA',
						tip: 'The country and state or province, if any, in which your business is located.',
						value: 'US:CA',
						options: expect.objectContaining( stateOptions ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_store_postcode',
						label: 'Postcode / ZIP',
						description:
							'The postal code, if any, in which your business is located.',
						type: 'text',
						default: '',
						tip: 'The postal code, if any, in which your business is located.',
						value: expect.any( String ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_allowed_countries',
						label: 'Selling location(s)',
						description:
							'This option lets you limit which countries you are willing to sell to.',
						type: 'select',
						default: 'all',
						tip: 'This option lets you limit which countries you are willing to sell to.',
						value: 'all',
						options: {
							all: 'Sell to all countries',
							all_except:
								'Sell to all countries, except for&hellip;',
							specific: 'Sell to specific countries',
						},
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_all_except_countries',
						label: 'Sell to all countries, except for&hellip;',
						description: '',
						type: 'multiselect',
						default: '',
						value: expect.anything(),
						options: expect.objectContaining( countries ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_specific_allowed_countries',
						label: 'Sell to specific countries',
						description: '',
						type: 'multiselect',
						default: '',
						value: expect.anything(),
						options: expect.objectContaining( countries ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_ship_to_countries',
						label: 'Shipping location(s)',
						description:
							'Choose which countries you want to ship to, or choose to ship to all locations you sell to.',
						type: 'select',
						default: '',
						tip: 'Choose which countries you want to ship to, or choose to ship to all locations you sell to.',
						value: expect.any( String ),
						options: expect.objectContaining( {
							'': 'Ship to all countries you sell to',
							all: 'Ship to all countries',
							specific: 'Ship to specific countries only',
							disabled:
								'Disable shipping &amp; shipping calculations',
						} ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_specific_ship_to_countries',
						label: 'Ship to specific countries',
						description: '',
						type: 'multiselect',
						default: '',
						value: expect.anything(),
						options: expect.objectContaining( countries ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_default_customer_address',
						label: 'Default customer location',
						description: '',
						type: 'select',
						default: 'base',
						tip: 'This option determines a customers default location. The MaxMind GeoLite Database will be periodically downloaded to your wp-content directory if using geolocation.',
						value: 'base',
						options: expect.objectContaining( {
							'': 'No location by default',
							base: 'Shop country/region',
							geolocation: 'Geolocate',
							geolocation_ajax:
								'Geolocate (with page caching support)',
						} ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_calc_taxes',
						label: 'Enable taxes',
						description: 'Enable tax rates and calculations',
						type: 'checkbox',
						default: 'no',
						tip: 'Rates will be configurable and taxes will be calculated during checkout.',
						value: expect.any( String ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_coupons',
						label: 'Enable coupons',
						description: 'Enable the use of coupon codes',
						type: 'checkbox',
						default: 'yes',
						tip: 'Coupons can be applied from the cart and checkout pages.',
						value: 'yes',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_calc_discounts_sequentially',
						label: '',
						description: 'Calculate coupon discounts sequentially',
						type: 'checkbox',
						default: 'no',
						tip: 'When applying multiple coupons, apply the first coupon to the full price and the second coupon to the discounted price and so on.',
						value: 'no',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_currency',
						label: 'Currency',
						description:
							'This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.',
						type: 'select',
						default: 'USD',
						options: expect.objectContaining( currencies ),
						tip: 'This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.',
						value: 'USD',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_currency_pos',
						label: 'Currency position',
						description:
							'This controls the position of the currency symbol.',
						type: 'select',
						default: 'left',
						options: {
							left: 'Left',
							right: 'Right',
							left_space: 'Left with space',
							right_space: 'Right with space',
						},
						tip: 'This controls the position of the currency symbol.',
						value: 'left',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_price_thousand_sep',
						label: 'Thousand separator',
						description:
							'This sets the thousand separator of displayed prices.',
						type: 'text',
						default: ',',
						tip: 'This sets the thousand separator of displayed prices.',
						value: ',',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_price_decimal_sep',
						label: 'Decimal separator',
						description:
							'This sets the decimal separator of displayed prices.',
						type: 'text',
						default: '.',
						tip: 'This sets the decimal separator of displayed prices.',
						value: '.',
					} ),
				] )
			);
		} );
	} );

	test.describe( 'Retrieve a settings option', () => {
		test( 'can retrieve a settings option', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/general/woocommerce_allowed_countries'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON ).toEqual(
				expect.objectContaining( {
					id: 'woocommerce_allowed_countries',
					label: 'Selling location(s)',
					description:
						'This option lets you limit which countries you are willing to sell to.',
					type: 'select',
					default: 'all',
					options: {
						all: 'Sell to all countries',
						all_except: 'Sell to all countries, except for&hellip;',
						specific: 'Sell to specific countries',
					},
					tip: 'This option lets you limit which countries you are willing to sell to.',
					value: 'all',
					group_id: 'general',
				} )
			);
		} );
	} );

	test.describe( 'Update a settings option', () => {
		test( 'can update a settings option', async ( { request } ) => {
			// call API to update settings options
			const response = await request.put(
				'./wp-json/wc/v3/settings/general/woocommerce_allowed_countries',
				{
					data: {
						value: 'all_except',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON ).toEqual(
				expect.objectContaining( {
					id: 'woocommerce_allowed_countries',
					label: 'Selling location(s)',
					description:
						'This option lets you limit which countries you are willing to sell to.',
					type: 'select',
					default: 'all',
					options: {
						all: 'Sell to all countries',
						all_except: 'Sell to all countries, except for&hellip;',
						specific: 'Sell to specific countries',
					},
					tip: 'This option lets you limit which countries you are willing to sell to.',
					value: 'all_except',
					group_id: 'general',
				} )
			);
		} );
	} );

	test.describe( 'Batch Update a settings option', () => {
		test( 'can batch update settings options', async ( { request } ) => {
			// call API to update settings options
			const response = await request.post(
				'./wp-json/wc/v3/settings/general/batch',
				{
					data: {
						update: [
							{
								id: 'woocommerce_allowed_countries',
								value: 'all_except',
							},
							{
								id: 'woocommerce_currency',
								value: 'GBP',
							},
						],
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// retrieve the updated settings values
			const countriesUpdatedResponse = await request.get(
				'./wp-json/wc/v3/settings/general/woocommerce_allowed_countries'
			);
			const countriesUpdatedResponseJSON =
				await countriesUpdatedResponse.json();
			expect( countriesUpdatedResponseJSON.value ).toEqual(
				'all_except'
			);

			const currencyUpdatedResponse = await request.get(
				'./wp-json/wc/v3/settings/general/woocommerce_currency'
			);
			const currencyUpdatedResponseJSON =
				await currencyUpdatedResponse.json();
			expect( currencyUpdatedResponseJSON.value ).toEqual( 'GBP' );

			// call API to restore the settings options
			await request.put( './wp-json/wc/v3/settings/general/batch', {
				data: {
					update: [
						{
							id: 'woocommerce_allowed_countries',
							value: 'all',
						},
						{
							id: 'woocommerce_currency',
							value: 'USD',
						},
					],
				},
			} );
		} );
	} );

	test.describe( 'List all Products settings options', () => {
		test( 'can retrieve all products settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/products'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_shop_page_id',
						label: 'Shop page',
						type: 'select',
						default: '',
						tip: 'This sets the base page of your shop - this is where your product archive will be.',
						value: expect.any( String ),
						options: expect.objectContaining( {} ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_cart_redirect_after_add',
						label: 'Add to cart behaviour',
						description:
							'Redirect to the cart page after successful addition',
						type: 'checkbox',
						default: 'no',
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_ajax_add_to_cart',
						label: '',
						description:
							'Enable AJAX add to cart buttons on archives',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_placeholder_image',
						label: 'Placeholder image',
						description: '',
						type: 'text',
						default: '',
						tip: 'This is the attachment ID, or image URL, used for placeholder images in the product catalog. Products with no image will use this.',
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_weight_unit',
						label: 'Weight unit',
						description:
							'This controls what unit you will define weights in.',
						type: 'select',
						default: 'lbs',
						options: {
							kg: 'kg',
							g: 'g',
							lbs: 'lbs',
							oz: 'oz',
						},
						tip: 'This controls what unit you will define weights in.',
						value: 'lbs',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_dimension_unit',
						label: 'Dimensions unit',
						description:
							'This controls what unit you will define lengths in.',
						type: 'select',
						default: 'in',
						options: {
							m: 'm',
							cm: 'cm',
							mm: 'mm',
							in: 'in',
							yd: 'yd',
						},
						tip: 'This controls what unit you will define lengths in.',
						value: 'in',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_reviews',
						label: 'Enable reviews',
						description: 'Enable product reviews',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_review_rating_verification_label',
						label: '',
						description:
							'Show "verified owner" label on customer reviews',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_review_rating_verification_required',
						label: '',
						description:
							'Reviews can only be left by "verified owners"',
						type: 'checkbox',
						default: 'no',
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_review_rating',
						label: 'Product ratings',
						description: 'Enable star rating on reviews',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_review_rating_required',
						label: '',
						description:
							'Star ratings should be required, not optional',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_manage_stock',
						label: 'Manage stock',
						description: 'Enable stock management',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_hold_stock_minutes',
						label: 'Hold stock (minutes)',
						description:
							'Hold stock (for unpaid orders) for x minutes. When this limit is reached, the pending order will be cancelled. Leave blank to disable.',
						type: 'number',
						default: '60',
						value: '60',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_notify_low_stock',
						label: 'Notifications',
						description: 'Enable low stock notifications',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_notify_no_stock',
						label: '',
						description: 'Enable out of stock notifications',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_stock_email_recipient',
						label: 'Notification recipient(s)',
						description:
							'Enter recipients (comma separated) that will receive this notification.',
						type: 'text',
						default: expect.any( String ),
						tip: 'Enter recipients (comma separated) that will receive this notification.',
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_notify_low_stock_amount',
						label: 'Low stock threshold',
						description:
							'When product stock reaches this amount you will be notified via email.',
						type: 'number',
						default: '2',
						tip: 'When product stock reaches this amount you will be notified via email.',
						value: '2',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_notify_no_stock_amount',
						label: 'Out of stock threshold',
						description:
							'When product stock reaches this amount the stock status will change to "out of stock" and you will be notified via email. This setting does not affect existing "in stock" products.',
						type: 'number',
						default: '0',
						tip: 'When product stock reaches this amount the stock status will change to "out of stock" and you will be notified via email. This setting does not affect existing "in stock" products.',
						value: '0',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_hide_out_of_stock_items',
						label: 'Out of stock visibility',
						description: 'Hide out of stock items from the catalog',
						type: 'checkbox',
						default: 'no',
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_stock_format',
						label: 'Stock display format',
						description:
							'This controls how stock quantities are displayed on the frontend.',
						type: 'select',
						default: '',
						options: {
							'': 'Always show quantity remaining in stock e.g. "12 in stock"',
							low_amount:
								'Only show quantity remaining in stock when low e.g. "Only 2 left in stock"',
							no_amount: 'Never show quantity remaining in stock',
						},
						tip: 'This controls how stock quantities are displayed on the frontend.',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_file_download_method',
						label: 'File download method',
						description:
							"If you are using X-Accel-Redirect download method along with NGINX server, make sure that you have applied settings as described in <a href='https://woocommerce.com/document/digital-downloadable-product-handling#nginx-setting'>Digital/Downloadable Product Handling</a> guide.",
						type: 'select',
						default: 'force',
						options: {
							force: 'Force downloads',
							xsendfile: 'X-Accel-Redirect/X-Sendfile',
							redirect: 'Redirect only (Insecure)',
						},
						tip: 'Forcing downloads will keep URLs hidden, but some servers may serve large files unreliably. If supported, <code>X-Accel-Redirect</code> / <code>X-Sendfile</code> can be used to serve downloads instead (server requires <code>mod_xsendfile</code>).',
						value: 'force',
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Tax settings options', () => {
		test( 'can retrieve all tax settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/tax'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_prices_include_tax',
						label: 'Prices entered with tax',
						description: '',
						type: 'radio',
						default: 'no',
						options: {
							yes: 'Yes, I will enter prices inclusive of tax',
							no: 'No, I will enter prices exclusive of tax',
						},
						tip: 'This option is important as it will affect how you input prices. If you select "Yes", enter prices including your base location\'s tax rate, the baseline for tax calculations. Changing this option will not update existing products.',
						value: 'no',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_tax_based_on',
						label: 'Calculate tax based on',
						description: '',
						type: 'select',
						default: 'shipping',
						options: {
							shipping: 'Customer shipping address',
							billing: 'Customer billing address',
							base: 'Shop base address',
						},
						tip: 'This option determines which address is used to calculate tax.',
						value: 'shipping',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_shipping_tax_class',
						label: 'Shipping tax class',
						description:
							'Optionally control which tax class shipping gets, or leave it so shipping tax is based on the cart items themselves.',
						type: 'select',
						default: 'inherit',
						options: expect.objectContaining( {
							inherit: 'Shipping tax class based on cart items',
							'': 'Standard',
						} ),
						tip: 'Optionally control which tax class shipping gets, or leave it so shipping tax is based on the cart items themselves.',
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_tax_round_at_subtotal',
						label: 'Rounding',
						description:
							'Round tax at subtotal level, instead of rounding per line',
						type: 'checkbox',
						default: 'no',
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_tax_classes',
						label: 'Additional tax classes',
						description: '',
						type: 'textarea',
						default: '',
						tip: 'List additional tax classes you need below (1 per line, e.g. Reduced Rates). These are in addition to "Standard rate" which exists by default.',
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_tax_display_shop',
						label: 'Display prices in the shop',
						description: '',
						type: 'select',
						default: 'excl',
						options: {
							incl: 'Including tax',
							excl: 'Excluding tax',
						},
						value: 'excl',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_tax_display_cart',
						label: 'Display prices during cart and checkout',
						description: '',
						type: 'select',
						default: 'excl',
						options: {
							incl: 'Including tax',
							excl: 'Excluding tax',
						},
						value: 'excl',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_price_display_suffix',
						label: 'Price display suffix',
						description: '',
						type: 'text',
						default: '',
						tip: 'Define text to show after your product prices. This could be, for example, "inc. Vat" to explain your pricing. You can also have prices substituted here using one of the following: {price_including_tax}, {price_excluding_tax}.',
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_tax_total_display',
						label: 'Display tax totals',
						description: '',
						type: 'select',
						default: 'itemized',
						options: {
							single: 'As a single total',
							itemized: 'Itemized',
						},
						value: 'itemized',
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Shipping settings options', () => {
		test( 'can retrieve all shipping settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/shipping'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_shipping_calc',
						label: 'Calculations',
						description:
							'Enable the shipping calculator on the cart page',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_shipping_cost_requires_address',
						label: '',
						description:
							'Hide shipping costs until an address is entered',
						type: 'checkbox',
						default: 'no',
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_ship_to_destination',
						label: 'Shipping destination',
						description:
							'This controls which shipping address is used by default.',
						type: 'radio',
						default: 'billing',
						options: {
							shipping: 'Default to customer shipping address',
							billing: 'Default to customer billing address',
							billing_only:
								'Force shipping to the customer billing address',
						},
						tip: 'This controls which shipping address is used by default.',
						value: 'billing',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_shipping_debug_mode',
						label: 'Debug mode',
						description: 'Enable debug mode',
						type: 'checkbox',
						default: 'no',
						tip: 'Enable shipping debug mode to show matching shipping zones and to bypass shipping rate cache.',
						value: 'no',
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Checkout settings options', () => {
		test( 'can retrieve all checkout settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/checkout'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual( expect.arrayContaining( [] ) );
		} );
	} );

	test.describe( 'List all Account settings options', () => {
		test( 'can retrieve all account settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/account'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_guest_checkout',
						label: 'Checkout',
						description: 'Enable guest checkout (recommended)',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_checkout_login_reminder',
						label: 'Login',
						description: 'Enable log-in during checkout',
						type: 'checkbox',
						default: 'no',
						value: expect.stringMatching( /no|yes/ ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_signup_and_login_from_checkout',
						label: 'Account creation',
						description: 'During checkout',
						type: 'checkbox',
						default: 'no',
						value: expect.stringMatching( /no|yes/ ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_enable_myaccount_registration',
						label: 'Account creation',
						description: 'On "My account" page',
						type: 'checkbox',
						default: 'no',
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_registration_generate_password',
						label: 'Account creation options',
						description: 'Send password setup link (recommended)',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_erasure_request_removes_order_data',
						label: 'Account erasure requests',
						description:
							'Remove personal data from orders on request',
						type: 'checkbox',
						default: 'no',
						tip: expect.stringMatching(
							'When handling an <a href="[^"]*wp-admin/erase-personal-data.php">account erasure request</a>, should personal data within orders be retained or removed?'
						),
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_erasure_request_removes_download_data',
						label: '',
						description: 'Remove access to downloads on request',
						type: 'checkbox',
						default: 'no',
						tip: expect.stringMatching(
							'When handling an <a href="[^"]*wp-admin/erase-personal-data.php">account erasure request</a>, should access to downloadable files be revoked and download logs cleared?'
						),
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_allow_bulk_remove_personal_data',
						label: 'Personal data removal',
						description:
							'Allow personal data to be removed in bulk from orders',
						type: 'checkbox',
						default: 'no',
						tip: 'Adds an option to the orders screen for removing personal data in bulk. Note that removing personal data cannot be undone.',
						value: 'no',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_registration_privacy_policy_text',
						label: 'Registration privacy policy',
						description: '',
						type: 'textarea',
						default:
							'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].',
						tip: 'Optionally add some text about your store privacy policy to show on account registration forms.',
						value: 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_checkout_privacy_policy_text',
						label: 'Checkout privacy policy',
						description: '',
						type: 'textarea',
						default:
							'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].',
						tip: 'Optionally add some text about your store privacy policy to show during checkout.',
						value: 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].',
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email settings options', () => {
		test( 'can retrieve all email settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_from_name',
						label: '"From" name',
						description: expect.any( String ),
						type: 'text',
						default: expect.any( String ),
						tip: expect.any( String ),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_from_address',
						label: '"From" address',
						description: '',
						type: 'email',
						default: expect.any( String ),
						tip: '',
						value: expect.any( String ),
					} ),
				] )
			);
			// woocommerce_email_header_image is custom slotfill and not included in the response
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_header_image_width',
						label: 'Logo width (px)',
						type: 'number',
						default: '120',
						value: expect.anything(), // value could be number or string depending on environment
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_header_alignment',
						label: 'Header alignment',
						description: '',
						type: 'select',
						default: 'left',
						value: 'left',
					} ),
				] )
			);
			// woocommerce_email_font_family is custom slotfill and not included in the response
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_footer_text',
						label: 'Footer text',
						description:
							'This text will appear in the footer of all of your WooCommerce emails. Available placeholders: {site_title} {site_url} {store_address} {store_email}',
						type: 'textarea',
						default: '{site_title}<br />{store_address}',
						tip: 'This text will appear in the footer of all of your WooCommerce emails. Available placeholders: {site_title} {site_url} {store_address} {store_email}',
						value: '{site_title}<br />{store_address}',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_base_color',
						label: 'Accent',
						description:
							'Customize the color of your buttons and links. Default <code>#720eec</code>.',
						type: 'color',
						default: '#720eec',
						tip: 'Customize the color of your buttons and links. Default <code>#720eec</code>.',
						value: expect.stringMatching( /^#[0-9A-Fa-f]{6}$/ ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_background_color',
						label: 'Email background',
						description:
							'Select a color for the background of your emails. Default <code>#f7f7f7</code>.',
						type: 'color',
						default: '#f7f7f7',
						tip: 'Select a color for the background of your emails. Default <code>#f7f7f7</code>.',
						value: expect.stringMatching( /^#[0-9A-Fa-f]{6}$/ ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_body_background_color',
						label: 'Content background',
						description:
							'Choose a background color for the content area of your emails. Default <code>#ffffff</code>.',
						type: 'color',
						default: '#ffffff',
						tip: 'Choose a background color for the content area of your emails. Default <code>#ffffff</code>.',
						value: expect.stringMatching( /^#[0-9A-Fa-f]{6}$/ ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_text_color',
						label: 'Heading & text',
						description:
							'Set the color of your headings and text. Default <code>#3c3c3c</code>.',
						type: 'color',
						default: '#3c3c3c',
						tip: 'Set the color of your headings and text. Default <code>#3c3c3c</code>.',
						value: expect.stringMatching( /^#[0-9A-Fa-f]{6}$/ ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_email_footer_text_color',
						label: 'Secondary text',
						description:
							'Choose a color for your secondary text, such as your footer content. Default <code>#3c3c3c</code>.',
						type: 'color',
						default: '#3c3c3c',
						tip: 'Choose a color for your secondary text, such as your footer content. Default <code>#3c3c3c</code>.',
						value: expect.stringMatching( /^#[0-9A-Fa-f]{6}$/ ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Advanced settings options', () => {
		test( 'can retrieve all advanced settings', async ( { request } ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/advanced'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_cart_page_id',
						label: 'Cart page',
						description:
							'Page where shoppers review their shopping cart',
						type: 'select',
						default: '',
						tip: 'Page where shoppers review their shopping cart',
						value: expect.any( String ),
						options: expect.any( Object ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_checkout_page_id',
						label: 'Checkout page',
						description:
							'Page where shoppers go to finalize their purchase',
						type: 'select',
						default: expect.any( Number ),
						tip: 'Page where shoppers go to finalize their purchase',
						value: expect.any( String ),
						options: expect.any( Object ),
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_page_id',
						label: 'My account page',
						description: 'Page contents: [woocommerce_my_account]',
						type: 'select',
						default: '',
						tip: 'Page contents: [woocommerce_my_account]',
						value: expect.any( String ),
						options: expect.any( Object ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_checkout_pay_endpoint',
						label: 'Pay',
						description:
							'Endpoint for the "Checkout &rarr; Pay" page.',
						type: 'text',
						default: 'order-pay',
						tip: 'Endpoint for the "Checkout &rarr; Pay" page.',
						value: 'order-pay',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_checkout_order_received_endpoint',
						label: 'Order received',
						description:
							'Endpoint for the "Checkout &rarr; Order received" page.',
						type: 'text',
						default: 'order-received',
						tip: 'Endpoint for the "Checkout &rarr; Order received" page.',
						value: 'order-received',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_add_payment_method_endpoint',
						label: 'Add payment method',
						description:
							'Endpoint for the "Checkout &rarr; Add payment method" page.',
						type: 'text',
						default: 'add-payment-method',
						tip: 'Endpoint for the "Checkout &rarr; Add payment method" page.',
						value: 'add-payment-method',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_delete_payment_method_endpoint',
						label: 'Delete payment method',
						description:
							'Endpoint for the delete payment method page.',
						type: 'text',
						default: 'delete-payment-method',
						tip: 'Endpoint for the delete payment method page.',
						value: 'delete-payment-method',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_orders_endpoint',
						label: 'Orders',
						description:
							'Endpoint for the "My account &rarr; Orders" page.',
						type: 'text',
						default: 'orders',
						tip: 'Endpoint for the "My account &rarr; Orders" page.',
						value: 'orders',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_view_order_endpoint',
						label: 'View order',
						description:
							'Endpoint for the "My account &rarr; View order" page.',
						type: 'text',
						default: 'view-order',
						tip: 'Endpoint for the "My account &rarr; View order" page.',
						value: 'view-order',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_downloads_endpoint',
						label: 'Downloads',
						description:
							'Endpoint for the "My account &rarr; Downloads" page.',
						type: 'text',
						default: 'downloads',
						tip: 'Endpoint for the "My account &rarr; Downloads" page.',
						value: 'downloads',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_edit_account_endpoint',
						label: 'Edit account',
						description:
							'Endpoint for the "My account &rarr; Edit account" page.',
						type: 'text',
						default: 'edit-account',
						tip: 'Endpoint for the "My account &rarr; Edit account" page.',
						value: 'edit-account',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_edit_address_endpoint',
						label: 'Addresses',
						description:
							'Endpoint for the "My account &rarr; Addresses" page.',
						type: 'text',
						default: 'edit-address',
						tip: 'Endpoint for the "My account &rarr; Addresses" page.',
						value: 'edit-address',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_payment_methods_endpoint',
						label: 'Payment methods',
						description:
							'Endpoint for the "My account &rarr; Payment methods" page.',
						type: 'text',
						default: 'payment-methods',
						tip: 'Endpoint for the "My account &rarr; Payment methods" page.',
						value: 'payment-methods',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_myaccount_lost_password_endpoint',
						label: 'Lost password',
						description:
							'Endpoint for the "My account &rarr; Lost password" page.',
						type: 'text',
						default: 'lost-password',
						tip: 'Endpoint for the "My account &rarr; Lost password" page.',
						value: 'lost-password',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'woocommerce_logout_endpoint',
						label: 'Logout',
						description:
							'Endpoint for the triggering logout. You can add this to your menus via a custom link: yoursite.com/?customer-logout=true',
						type: 'text',
						default: 'customer-logout',
						tip: 'Endpoint for the triggering logout. You can add this to your menus via a custom link: yoursite.com/?customer-logout=true',
						value: 'customer-logout',
					} ),
				] )
			);

			// Skip these tests in WPCOM because they're not configurable there by design.
			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( ! process.env.IS_WPCOM ) {
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'woocommerce_allow_tracking',
							label: 'Enable tracking',
							description:
								'Allow usage of WooCommerce to be tracked',
							type: 'checkbox',
							default: 'no',
							tip: 'To opt out, leave this box unticked. Your store remains untracked, and no data will be collected. Read about what usage data is tracked at: <a href="https://woocommerce.com/usage-tracking" target="_blank">WooCommerce.com Usage Tracking Documentation</a>.',
							value: expect.any( String ),
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'woocommerce_show_marketplace_suggestions',
							label: 'Show Suggestions',
							description:
								'Display suggestions within WooCommerce',
							type: 'checkbox',
							default: 'yes',
							tip: 'Leave this box unchecked if you do not want to pull suggested extensions from WooCommerce.com.',
							value: expect.any( String ),
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'woocommerce_analytics_enabled',
							label: 'Analytics',
							description: 'Enable WooCommerce Analytics',
							type: 'checkbox',
							default: 'yes',
							value: expect.any( String ),
						} ),
					] )
				);
			}
		} );
	} );

	test.describe( 'List all Email New Order settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test(
			'can retrieve all email new order settings',
			{ tag: [ tags.SKIP_ON_PRESSABLE, tags.SKIP_ON_WPCOM ] },
			async ( { request } ) => {
				// call API to retrieve all settings options
				const response = await request.get(
					'./wp-json/wc/v3/settings/email_new_order'
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( Array.isArray( responseJSON ) ).toBe( true );
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'enabled',
							label: 'Enable/Disable',
							description: '',
							type: 'checkbox',
							default: 'yes',
							value: 'yes',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'recipient',
							label: 'Recipient(s)',
							description: expect.stringContaining(
								'Enter recipients (comma separated) for this email. Defaults to'
							),
							type: 'text',
							default: '',
							tip: expect.stringContaining(
								'Enter recipients (comma separated) for this email. Defaults to'
							),
							value: expect.any( String ),
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'subject',
							label: 'Subject',
							description:
								'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
							type: 'text',
							default: '',
							tip: 'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
							value: '',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'heading',
							label: 'Email heading',
							description:
								'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
							type: 'text',
							default: '',
							tip: 'Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
							value: '',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'additional_content',
							label: 'Additional content',
							description:
								'Text to appear below the main email content. Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
							type: 'textarea',
							default: 'Congratulations on the sale!',
							tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}</code>, <code>{site_address}</code>, <code>{site_url}</code>, <code>{store_email}</code>, <code>{order_date}</code>, <code>{order_number}</code>',
							value: 'Congratulations on the sale!',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'email_type',
							label: 'Email type',
							description:
								'Choose which format of email to send.',
							type: 'select',
							default: 'html',
							options: {
								plain: 'Plain text',
								html: 'HTML',
								multipart: 'Multipart',
							},
							tip: 'Choose which format of email to send.',
							value: 'html',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'cc',
							label: 'Cc(s)',
							description: expect.stringContaining(
								'Enter Cc recipients (comma-separated) for this email.'
							),
							type: 'text',
							default: '',
							tip: expect.stringContaining(
								'Enter Cc recipients (comma-separated) for this email.'
							),
							value: expect.any( String ),
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'bcc',
							label: 'Bcc(s)',
							description: expect.stringContaining(
								'Enter Bcc recipients (comma-separated) for this email.'
							),
							type: 'text',
							default: '',
							tip: expect.stringContaining(
								'Enter Bcc recipients (comma-separated) for this email.'
							),
							value: expect.any( String ),
						} ),
					] )
				);
			}
		);
	} );

	test.describe( 'List all Email Failed Order settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test(
			'can retrieve all email failed order settings',
			{ tag: [ tags.SKIP_ON_PRESSABLE, tags.SKIP_ON_WPCOM ] },
			async ( { request } ) => {
				// call API to retrieve all settings options
				const response = await request.get(
					'./wp-json/wc/v3/settings/email_failed_order'
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( Array.isArray( responseJSON ) ).toBe( true );
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'enabled',
							label: 'Enable/Disable',
							description: '',
							type: 'checkbox',
							default: 'yes',
							value: 'yes',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'recipient',
							label: 'Recipient(s)',
							description: expect.stringContaining(
								'Enter recipients (comma separated) for this email. Defaults to'
							),
							type: 'text',
							default: '',
							tip: expect.stringContaining(
								'Enter recipients (comma separated) for this email. Defaults to'
							),
							value: expect.any( String ),
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'subject',
							label: 'Subject',
							description:
								'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
							type: 'text',
							default: '',
							tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
							value: '',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'heading',
							label: 'Email heading',
							description:
								'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
							type: 'text',
							default: '',
							tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
							value: '',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'additional_content',
							label: 'Additional content',
							description:
								'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
							type: 'textarea',
							default:
								'We hope they’ll be back soon! Read more about <a href="https://woocommerce.com/document/managing-orders/">troubleshooting failed payments</a>.',
							tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
							value: 'We hope they’ll be back soon! Read more about <a href="https://woocommerce.com/document/managing-orders/">troubleshooting failed payments</a>.',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'email_type',
							label: 'Email type',
							description:
								'Choose which format of email to send.',
							type: 'select',
							default: 'html',
							options: {
								plain: 'Plain text',
								html: 'HTML',
								multipart: 'Multipart',
							},
							tip: 'Choose which format of email to send.',
							value: 'html',
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'cc',
							label: 'Cc(s)',
							description: expect.stringContaining(
								'Enter Cc recipients (comma-separated) for this email.'
							),
							type: 'text',
							default: '',
							tip: expect.stringContaining(
								'Enter Cc recipients (comma-separated) for this email.'
							),
							value: expect.any( String ),
						} ),
					] )
				);
				expect( responseJSON ).toEqual(
					expect.arrayContaining( [
						expect.objectContaining( {
							id: 'bcc',
							label: 'Bcc(s)',
							description: expect.stringContaining(
								'Enter Bcc recipients (comma-separated) for this email.'
							),
							type: 'text',
							default: '',
							tip: expect.stringContaining(
								'Enter Bcc recipients (comma-separated) for this email.'
							),
							value: expect.any( String ),
						} ),
					] )
				);
			}
		);
	} );

	test.describe( 'List all Email Customer On Hold Order settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer on hold order settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_on_hold_order'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'enabled',
						label: 'Enable/Disable',
						description: '',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject',
						label: 'Subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading',
						label: 'Email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'textarea',
						default:
							'Thanks again! If you need any help with your order, please contact us at {store_email}.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: 'Thanks again! If you need any help with your order, please contact us at {store_email}.',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email Customer Processing Order settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer processing order settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_processing_order'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'enabled',
						label: 'Enable/Disable',
						description: '',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject',
						label: 'Subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading',
						label: 'Email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'textarea',
						default:
							'Thanks again! If you need any help with your order, please contact us at {store_email}.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: 'Thanks again! If you need any help with your order, please contact us at {store_email}.',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email Customer Completed Order settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer completed order settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_completed_order'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'enabled',
						label: 'Enable/Disable',
						description: '',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject',
						label: 'Subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading',
						label: 'Email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'textarea',
						default:
							'Thanks again! If you need any help with your order, please contact us at {store_email}.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: 'Thanks again! If you need any help with your order, please contact us at {store_email}.',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email Customer Refunded Order settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer refunded order settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_refunded_order'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'enabled',
						label: 'Enable/Disable',
						description: '',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject_full',
						label: 'Full refund subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject_partial',
						label: 'Partial refund subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading_full',
						label: 'Full refund email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading_partial',
						label: 'Partial refund email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'textarea',
						default:
							'If you need any help with your order, please contact us at {store_email}.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: 'If you need any help with your order, please contact us at {store_email}.',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email Customer Invoice settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer invoice settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_invoice'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject',
						label: 'Subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject_paid',
						label: 'Subject (paid)',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading_paid',
						label: 'Email heading (paid)',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);

			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'textarea',
						default:
							'Thanks again! If you need any help with your order, please contact us at {store_email}.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: 'Thanks again! If you need any help with your order, please contact us at {store_email}.',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email Customer Note settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer note settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_note'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'enabled',
						label: 'Enable/Disable',
						description: '',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject',
						label: 'Subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading',
						label: 'Email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						type: 'textarea',
						default:
							'Thanks again! If you need any help with your order, please contact us at {store_email}.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}&lt;/code&gt;, &lt;code&gt;{order_date}&lt;/code&gt;, &lt;code&gt;{order_number}</code>',
						value: 'Thanks again! If you need any help with your order, please contact us at {store_email}.',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email Customer Reset Password settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer reset password settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_reset_password'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'enabled',
						label: 'Enable/Disable',
						description: '',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject',
						label: 'Subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading',
						label: 'Email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						type: 'textarea',
						default: 'Thanks for reading.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						value: 'Thanks for reading.',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );

	test.describe( 'List all Email Customer New Account settings', () => {
		test.beforeAll( enableEmailImprovementsFeature );
		test.afterAll( disableEmailImprovementsFeature );
		test( 'can retrieve all email customer new account settings', async ( {
			request,
		} ) => {
			// call API to retrieve all settings options
			const response = await request.get(
				'./wp-json/wc/v3/settings/email_customer_new_account'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'enabled',
						label: 'Enable/Disable',
						description: '',
						type: 'checkbox',
						default: 'yes',
						value: 'yes',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'subject',
						label: 'Subject',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'heading',
						label: 'Email heading',
						description:
							'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						type: 'text',
						default: '',
						tip: 'Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						value: '',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'additional_content',
						label: 'Additional content',
						description:
							'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						type: 'textarea',
						default: 'We look forward to seeing you soon.',
						tip: 'Text to appear below the main email content. Available placeholders: <code>{site_title}&lt;/code&gt;, &lt;code&gt;{site_address}&lt;/code&gt;, &lt;code&gt;{site_url}&lt;/code&gt;, &lt;code&gt;{store_email}</code>',
						value: 'We look forward to seeing you soon.',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'email_type',
						label: 'Email type',
						description: 'Choose which format of email to send.',
						type: 'select',
						default: 'html',
						options: {
							plain: 'Plain text',
							html: 'HTML',
							multipart: 'Multipart',
						},
						tip: 'Choose which format of email to send.',
						value: 'html',
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'cc',
						label: 'Cc(s)',
						description: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Cc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						id: 'bcc',
						label: 'Bcc(s)',
						description: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						type: 'text',
						default: '',
						tip: expect.stringContaining(
							'Enter Bcc recipients (comma-separated) for this email.'
						),
						value: expect.any( String ),
					} ),
				] )
			);
		} );
	} );
} );
