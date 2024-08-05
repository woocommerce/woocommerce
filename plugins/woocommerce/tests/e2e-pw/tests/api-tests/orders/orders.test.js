const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );
const { order } = require( '../../../data' );

/**
 * Billing properties to update.
 */
const updatedCustomerBilling = {
	first_name: 'Jane',
	last_name: 'Doe',
	company: 'Automattic',
	country: 'US',
	address_1: '123 Market Street',
	address_2: 'Suite 500',
	city: 'Austin',
	state: 'TX',
	postcode: '73301',
	phone: '123456789',
	email: 'jane.doe@example.com',
};

/**
 * Shipping properties to update.
 */
const updatedCustomerShipping = {
	first_name: 'Mike',
	last_name: 'Anderson',
	company: 'Automattic',
	country: 'US',
	address_1: '123 Ocean Ave',
	address_2: '',
	city: 'New York',
	state: 'NY',
	postcode: '10013',
	phone: '123456789',
};

test.describe.serial( 'Orders API tests', () => {
	let orderId, sampleData;

	test.beforeAll( async ( { request } ) => {
		const createSampleCategories = async () => {
			const clothing = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Clothing',
					},
				}
			);
			const clothingJSON = await clothing.json();

			const accessories = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Accessories',
						parent: clothingJSON.id,
					},
				}
			);
			const accessoriesJSON = await accessories.json();

			const hoodies = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Hoodies',
						parent: clothingJSON.id,
					},
				}
			);
			const hoodiesJSON = await hoodies.json();

			const tshirts = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Tshirts',
						parent: clothingJSON.id,
					},
				}
			);
			const tshirtsJSON = await tshirts.json();

			const decor = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Decor',
					},
				}
			);
			const decorJSON = await decor.json();

			const music = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Music',
					},
				}
			);
			const musicJSON = await music.json();

			return {
				clothingJSON,
				accessoriesJSON,
				hoodiesJSON,
				tshirtsJSON,
				decorJSON,
				musicJSON,
			};
		};

		const createSampleAttributes = async () => {
			const color = await request.post(
				'/wp-json/wc/v3/products/attributes',
				{
					data: {
						name: 'Color',
					},
				}
			);
			const colorJSON = await color.json();

			const size = await request.post(
				'/wp-json/wc/v3/products/attributes',
				{
					data: {
						name: 'Size',
					},
				}
			);
			const sizeJSON = await size.json();

			const colorNames = [ 'Blue', 'Gray', 'Green', 'Red', 'Yellow' ];

			const colorNamesObjectArray = colorNames.map( ( name ) => ( {
				name,
			} ) );

			const colors = await request.post(
				`/wp-json/wc/v3/products/attributes/${ colorJSON.id }/terms/batch`,
				{
					data: {
						create: colorNamesObjectArray,
					},
				}
			);

			const colorsJSON = await colors.json();

			const sizeNames = [ 'Large', 'Medium', 'Small' ];

			const sizeNamesObjectArray = sizeNames.map( ( name ) => ( {
				name,
			} ) );

			const sizes = await request.post(
				`/wp-json/wc/v3/products/attributes/${ sizeJSON.id }/terms/batch`,
				{
					data: {
						create: sizeNamesObjectArray,
					},
				}
			);
			const sizesJSON = await sizes.json();

			return {
				colorJSON,
				colors: colorsJSON.create,
				sizeJSON,
				sizes: sizesJSON.create,
			};
		};

		const createSampleTags = async () => {
			const cool = await request.post( '/wp-json/wc/v3/products/tags', {
				data: {
					name: 'Cool',
				},
			} );
			const coolJSON = await cool.json();

			return {
				coolJSON,
			};
		};

		const createSampleShippingClasses = async () => {
			const freight = await request.post(
				'/wp-json/wc/v3/products/shipping_classes',
				{
					data: {
						name: 'Freight',
					},
				}
			);
			const freightJSON = await freight.json();

			return {
				freightJSON,
			};
		};

		const createSampleTaxClasses = async () => {
			//check to see if Reduced Rate tax class exists - if not, create it
			let reducedRate = await request.get(
				'/wp-json/wc/v3/taxes/classes/reduced-rate'
			);
			let reducedRateJSON = await reducedRate.json();
			expect( Array.isArray( reducedRateJSON ) ).toBe( true );

			//if tax class does not exist then create it
			if ( reducedRateJSON.length < 1 ) {
				reducedRate = await request.post(
					'/wp-json/wc/v3/taxes/classes',
					{
						data: {
							name: 'Reduced Rate',
						},
					}
				);
				reducedRateJSON = await reducedRate.json();
				return { reducedRateJSON };
			}

			// return an empty object as nothing new was created so nothing will
			// need deleted during cleanup
			return {};
		};

		const createSampleSimpleProducts = async (
			categories,
			attributes,
			tags
		) => {
			const description =
				'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. ' +
				'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. ' +
				'Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n';

			const simpleProducts = await request.post(
				'/wp-json/wc/v3/products/batch',
				{
					data: {
						create: [
							{
								name: 'Beanie with Logo oxo',
								date_created_gmt: '2021-09-01T15:50:20',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'Woo-beanie-logo',
								price: '18',
								regular_price: '20',
								sale_price: '18',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '0.2',
								dimensions: {
									length: '6',
									width: '4',
									height: '1',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.accessoriesJSON.id,
									},
								],
								tags: [],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Red' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 62, 63, 61, 60 ],
								stock_status: 'instock',
							},
							{
								name: 'T-Shirt with Logo oxo',
								date_created_gmt: '2021-09-02T15:50:20',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'Woo-tshirt-logo',
								price: '18',
								regular_price: '18',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '0.5',
								dimensions: {
									length: '10',
									width: '12',
									height: '0.5',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.tshirtsJSON.id,
									},
								],
								tags: [],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Gray' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 59, 67, 66, 56 ],
								stock_status: 'instock',
							},
							{
								name: 'Single oxo',
								date_created_gmt: '2021-09-03T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple, virtual product.</p>\n',
								sku: 'woo-single',
								price: '2',
								regular_price: '3',
								sale_price: '2',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								purchasable: true,
								total_sales: 0,
								virtual: true,
								downloadable: true,
								downloads: [
									{
										id: '2579cf07-8b08-4c25-888a-b6258dd1f035',
										name: 'Single',
										file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg',
									},
								],
								download_limit: 1,
								download_expiry: 1,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '',
								dimensions: {
									length: '',
									width: '',
									height: '',
								},
								shipping_required: false,
								shipping_taxable: false,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.musicJSON.id,
									},
								],
								tags: [],
								attributes: [],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 68 ],
								stock_status: 'instock',
							},
							{
								name: 'Album oxo',
								date_created_gmt: '2021-09-04T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple, virtual product.</p>\n',
								sku: 'woo-album',
								price: '15',
								regular_price: '15',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: true,
								downloadable: true,
								downloads: [
									{
										id: 'cc10249f-1de2-44d4-93d3-9f88ae629f76',
										name: 'Single 1',
										file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg',
									},
									{
										id: 'aea8ef69-ccdc-4d83-8e21-3c395ebb9411',
										name: 'Single 2',
										file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/album.jpg',
									},
								],
								download_limit: 1,
								download_expiry: 1,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '',
								dimensions: {
									length: '',
									width: '',
									height: '',
								},
								shipping_required: false,
								shipping_taxable: false,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.musicJSON.id,
									},
								],
								tags: [],
								attributes: [],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 69 ],
								stock_status: 'instock',
							},
							{
								name: 'Polo oxo',
								date_created_gmt: '2021-09-05T15:50:19',
								type: 'simple',
								status: 'pending',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-polo',
								price: '20',
								regular_price: '20',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '0.8',
								dimensions: {
									length: '6',
									width: '5',
									height: '1',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.tshirtsJSON.id,
									},
								],
								tags: [],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Blue' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 59, 56, 66, 76 ],
								stock_status: 'instock',
							},
							{
								name: 'Long Sleeve Tee oxo',
								date_created_gmt: '2021-09-06T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-long-sleeve-tee',
								price: '25',
								regular_price: '25',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '1',
								dimensions: {
									length: '7',
									width: '5',
									height: '1',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: 'freight',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.tshirtsJSON.id,
									},
								],
								tags: [],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Green' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 59, 56, 76, 67 ],
								stock_status: 'instock',
							},
							{
								name: 'Hoodie with Zipper oxo',
								date_created_gmt: '2021-09-07T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-hoodie-with-zipper',
								price: '45',
								regular_price: '45',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '2',
								dimensions: {
									length: '8',
									width: '6',
									height: '2',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.hoodiesJSON.id,
									},
								],
								tags: [],
								attributes: [],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 57, 58 ],
								stock_status: 'instock',
							},
							{
								name: 'Hoodie with Pocket oxo',
								date_created_gmt: '2021-09-08T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'hidden',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-hoodie-with-pocket',
								price: '35',
								regular_price: '45',
								sale_price: '35',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '3',
								dimensions: {
									length: '10',
									width: '8',
									height: '2',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.hoodiesJSON.id,
									},
								],
								tags: [
									{
										id: tags.coolJSON.id,
									},
								],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Gray' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 65, 57, 58 ],
								stock_status: 'instock',
							},
							{
								name: 'Sunglasses oxo',
								date_created_gmt: '2021-09-09T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-sunglasses',
								price: '90',
								regular_price: '90',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: 'reduced-rate',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '0.2',
								dimensions: {
									length: '4',
									width: '1.4',
									height: '1',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.accessoriesJSON.id,
									},
								],
								tags: [
									{
										id: tags.coolJSON.id,
									},
								],
								attributes: [],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 60, 62, 77, 61 ],
								stock_status: 'instock',
							},
							{
								name: 'Cap oxo',
								date_created_gmt: '2021-09-10T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-cap',
								price: '16',
								regular_price: '18',
								sale_price: '16',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '0.6',
								dimensions: {
									length: '8',
									width: '6.5',
									height: '4',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.accessoriesJSON.id,
									},
								],
								tags: [],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Yellow' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 60, 77, 61, 63 ],
								stock_status: 'instock',
							},
							{
								name: 'Belt oxo',
								date_created_gmt: '2021-09-12T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-belt',
								price: '55',
								regular_price: '65',
								sale_price: '55',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '1.2',
								dimensions: {
									length: '12',
									width: '2',
									height: '1.5',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.accessoriesJSON.id,
									},
								],
								tags: [],
								attributes: [],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 63, 77, 62, 60 ],
								stock_status: 'instock',
							},
							{
								name: 'Beanie oxo',
								date_created_gmt: '2021-09-13T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-beanie',
								price: '18',
								regular_price: '20',
								sale_price: '18',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '0.2',
								dimensions: {
									length: '4',
									width: '5',
									height: '0.5',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.accessoriesJSON.id,
									},
								],
								tags: [
									{
										id: tags.coolJSON.id,
									},
								],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Red' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 63, 62, 61, 77 ],
								stock_status: 'instock',
							},
							{
								name: 'T-Shirt oxo',
								date_created_gmt: '2021-09-14T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-tshirt',
								price: '18',
								regular_price: '18',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '0.8',
								dimensions: {
									length: '8',
									width: '6',
									height: '1',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.tshirtsJSON.id,
									},
								],
								tags: [],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Gray' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 67, 76, 56, 66 ],
								stock_status: 'onbackorder',
							},
							{
								name: 'Hoodie with Logo oxo',
								date_created_gmt: '2021-09-15T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-hoodie-with-logo',
								price: '45',
								regular_price: '45',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: true,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '2',
								dimensions: {
									length: '10',
									width: '6',
									height: '3',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.hoodiesJSON.id,
									},
								],
								tags: [],
								attributes: [
									{
										id: attributes.colorJSON.id,
										position: 0,
										visible: true,
										variation: false,
										options: [ 'Blue' ],
									},
								],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [ 57, 65 ],
								stock_status: 'instock',
							},
						],
					},
				}
			);
			const simpleProductsJSON = await simpleProducts.json();

			return simpleProductsJSON.create;
		};

		const createSampleExternalProducts = async ( categories ) => {
			const externalProducts = await request.post(
				'/wp-json/wc/v3/products/batch',
				{
					data: {
						create: [
							{
								name: 'WordPress Pennant oxo',
								date_created_gmt: '2021-09-16T15:50:20',
								type: 'external',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description:
									'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. ' +
									'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. ' +
									'Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n',
								short_description:
									'<p>This is an external product.</p>\n',
								sku: 'wp-pennant',
								price: '11.05',
								regular_price: '11.05',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								purchasable: false,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url:
									'https://mercantile.wordpress.org/product/wordpress-pennant/',
								button_text: 'Buy on the WordPress swag store!',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '',
								dimensions: {
									length: '',
									width: '',
									height: '',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.decorJSON.id,
									},
								],
								tags: [],
								attributes: [],
								default_attributes: [],
								variations: [],
								grouped_products: [],
								menu_order: 0,
								related_ids: [],
								stock_status: 'instock',
							},
						],
					},
				}
			);
			const externalProductsJSON = await externalProducts.json();

			return externalProductsJSON.create;
		};

		const createSampleGroupedProduct = async ( categories ) => {
			const logoProducts = await request.get( '/wp-json/wc/v3/products', {
				params: {
					search: 'logo',
					_fields: [ 'id' ],
				},
			} );
			const logoProductsJSON = await logoProducts.json();

			const groupedProducts = await request.post(
				'/wp-json/wc/v3/products/batch',
				{
					data: {
						create: [
							{
								name: 'Logo Collection oxo',
								date_created_gmt: '2021-09-17T15:50:20',
								type: 'grouped',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description:
									'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. ' +
									'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. ' +
									'Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n',
								short_description:
									'<p>This is a grouped product.</p>\n',
								sku: 'logo-collection',
								price: '18',
								regular_price: '',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								purchasable: false,
								total_sales: 0,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								external_url: '',
								button_text: '',
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								sold_individually: false,
								weight: '',
								dimensions: {
									length: '',
									width: '',
									height: '',
								},
								shipping_required: true,
								shipping_taxable: true,
								shipping_class: '',
								reviews_allowed: true,
								average_rating: '0.00',
								rating_count: 0,
								upsell_ids: [],
								cross_sell_ids: [],
								parent_id: 0,
								purchase_note: '',
								categories: [
									{
										id: categories.clothingJSON.id,
									},
								],
								tags: [],
								attributes: [],
								default_attributes: [],
								variations: [],
								grouped_products: logoProductsJSON.map(
									( p ) => p.id
								),
								menu_order: 0,
								related_ids: [],
								stock_status: 'instock',
							},
						],
					},
				}
			);
			const groupedProductsJSON = await groupedProducts.json();

			return groupedProductsJSON.create;
		};

		const createSampleVariableProducts = async (
			categories,
			attributes
		) => {
			const description =
				'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. ' +
				'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. ' +
				'Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n';

			const hoodie = await request.post( '/wp-json/wc/v3/products', {
				data: {
					name: 'Hoodie oxo',
					date_created_gmt: '2021-09-18T15:50:19',
					type: 'variable',
					status: 'publish',
					featured: false,
					catalog_visibility: 'visible',
					description,
					short_description: '<p>This is a variable product.</p>\n',
					sku: 'woo-hoodie',
					price: '42',
					regular_price: '',
					sale_price: '',
					date_on_sale_from_gmt: null,
					date_on_sale_to_gmt: null,
					on_sale: true,
					purchasable: true,
					total_sales: 0,
					virtual: false,
					downloadable: false,
					downloads: [],
					download_limit: 0,
					download_expiry: 0,
					external_url: '',
					button_text: '',
					tax_status: 'taxable',
					tax_class: '',
					manage_stock: false,
					stock_quantity: null,
					backorders: 'no',
					backorders_allowed: false,
					backordered: false,
					low_stock_amount: null,
					sold_individually: false,
					weight: '1.5',
					dimensions: {
						length: '10',
						width: '8',
						height: '3',
					},
					shipping_required: true,
					shipping_taxable: true,
					shipping_class: '',
					reviews_allowed: true,
					average_rating: '0.00',
					rating_count: 0,
					upsell_ids: [],
					cross_sell_ids: [],
					parent_id: 0,
					purchase_note: '',
					categories: [
						{
							id: categories.hoodiesJSON.id,
						},
					],
					tags: [],
					attributes: [
						{
							id: attributes.colorJSON.id,
							position: 0,
							visible: true,
							variation: true,
							options: [ 'Blue', 'Green', 'Red' ],
						},
						{
							id: 0,
							name: 'Logo',
							position: 1,
							visible: true,
							variation: true,
							options: [ 'Yes', 'No' ],
						},
					],
					default_attributes: [],
					grouped_products: [],
					menu_order: 0,
					stock_status: 'instock',
				},
			} );
			const hoodieJSON = await hoodie.json();

			const variationDescription =
				'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. ' +
				'Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. ' +
				'Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. ' +
				'Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. ' +
				'Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. ' +
				'Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.</p>\n';

			const hoodieVariations = await request.post(
				`/wp-json/wc/v3/products/${ hoodieJSON.id }/variations/batch`,
				{
					data: {
						create: [
							{
								date_created_gmt: '2021-09-19T15:50:20',
								description: variationDescription,
								sku: 'woo-hoodie-blue-logo',
								price: '45',
								regular_price: '45',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								status: 'publish',
								purchasable: true,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								stock_status: 'instock',
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								weight: '1.5',
								dimensions: {
									length: '10',
									width: '8',
									height: '3',
								},
								shipping_class: '',
								attributes: [
									{
										id: attributes.colorJSON.id,
										option: 'Blue',
									},
									{
										id: 0,
										name: 'Logo',
										option: 'Yes',
									},
								],
								menu_order: 0,
							},
							{
								date_created_gmt: '2021-09-20T15:50:20',
								description: variationDescription,
								sku: 'woo-hoodie-blue',
								price: '45',
								regular_price: '45',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								status: 'publish',
								purchasable: true,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								stock_status: 'instock',
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								weight: '1.5',
								dimensions: {
									length: '10',
									width: '8',
									height: '3',
								},
								shipping_class: '',
								attributes: [
									{
										id: attributes.colorJSON.id,
										option: 'Blue',
									},
									{
										id: 0,
										name: 'Logo',
										option: 'No',
									},
								],
								menu_order: 3,
							},
							{
								date_created_gmt: '2021-09-21T15:50:20',
								description: variationDescription,
								sku: 'woo-hoodie-green',
								price: '45',
								regular_price: '45',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								status: 'publish',
								purchasable: true,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								stock_status: 'instock',
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								weight: '1.5',
								dimensions: {
									length: '10',
									width: '8',
									height: '3',
								},
								shipping_class: '',
								attributes: [
									{
										id: attributes.colorJSON.id,
										option: 'Green',
									},
									{
										id: 0,
										name: 'Logo',
										option: 'No',
									},
								],
								menu_order: 2,
							},
							{
								date_created_gmt: '2021-09-22T15:50:19',
								description: variationDescription,
								sku: 'woo-hoodie-red',
								price: '42',
								regular_price: '45',
								sale_price: '42',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: true,
								status: 'publish',
								purchasable: true,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								stock_status: 'instock',
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								weight: '1.5',
								dimensions: {
									length: '10',
									width: '8',
									height: '3',
								},
								shipping_class: '',
								attributes: [
									{
										id: attributes.colorJSON.id,
										option: 'Red',
									},
									{
										id: 0,
										name: 'Logo',
										option: 'No',
									},
								],
								menu_order: 1,
							},
						],
					},
				}
			);
			const hoodieVariationsJSON = await hoodieVariations.json();

			const vneck = await request.post( '/wp-json/wc/v3/products', {
				data: {
					name: 'V-Neck T-Shirt oxo',
					date_created_gmt: '2021-09-23T15:50:19',
					type: 'variable',
					status: 'publish',
					featured: true,
					catalog_visibility: 'visible',
					description,
					short_description: '<p>This is a variable product.</p>\n',
					sku: 'woo-vneck-tee',
					price: '15',
					regular_price: '',
					sale_price: '',
					date_on_sale_from_gmt: null,
					date_on_sale_to_gmt: null,
					on_sale: false,
					purchasable: true,
					total_sales: 0,
					virtual: false,
					downloadable: false,
					downloads: [],
					download_limit: 0,
					download_expiry: 0,
					external_url: '',
					button_text: '',
					tax_status: 'taxable',
					tax_class: '',
					manage_stock: false,
					stock_quantity: null,
					backorders: 'no',
					backorders_allowed: false,
					backordered: false,
					low_stock_amount: null,
					sold_individually: false,
					weight: '0.5',
					dimensions: {
						length: '24',
						width: '1',
						height: '2',
					},
					shipping_required: true,
					shipping_taxable: true,
					shipping_class: '',
					reviews_allowed: true,
					average_rating: '0.00',
					rating_count: 0,
					upsell_ids: [],
					cross_sell_ids: [],
					parent_id: 0,
					purchase_note: '',
					categories: [
						{
							id: categories.tshirtsJSON.id,
						},
					],
					tags: [],
					attributes: [
						{
							id: attributes.colorJSON.id,
							position: 0,
							visible: true,
							variation: true,
							options: [ 'Blue', 'Green', 'Red' ],
						},
						{
							id: attributes.sizeJSON.id,
							position: 1,
							visible: true,
							variation: true,
							options: [ 'Large', 'Medium', 'Small' ],
						},
					],
					default_attributes: [],
					grouped_products: [],
					menu_order: 0,
					stock_status: 'instock',
				},
			} );
			const vneckJSON = await vneck.json();

			const vneckVariations = await request.post(
				`/wp-json/wc/v3/products/${ vneckJSON.id }/variations/batch`,
				{
					data: {
						create: [
							{
								date_created_gmt: '2021-09-24T15:50:19',
								description: variationDescription,
								sku: 'woo-vneck-tee-blue',
								price: '15',
								regular_price: '15',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								status: 'publish',
								purchasable: true,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								stock_status: 'instock',
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								weight: '0.5',
								dimensions: {
									length: '24',
									width: '1',
									height: '2',
								},
								shipping_class: '',
								attributes: [
									{
										id: attributes.colorJSON.id,
										option: 'Blue',
									},
								],
								menu_order: 0,
							},
							{
								date_created_gmt: '2021-09-25T15:50:19',
								description: variationDescription,
								sku: 'woo-vneck-tee-green',
								price: '20',
								regular_price: '20',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								status: 'publish',
								purchasable: true,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								stock_status: 'instock',
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								weight: '0.5',
								dimensions: {
									length: '24',
									width: '1',
									height: '2',
								},
								shipping_class: '',
								attributes: [
									{
										id: attributes.colorJSON.id,
										option: 'Green',
									},
								],
								menu_order: 0,
							},
							{
								date_created_gmt: '2021-09-26T15:50:19',
								description: variationDescription,
								sku: 'woo-vneck-tee-red',
								price: '20',
								regular_price: '20',
								sale_price: '',
								date_on_sale_from_gmt: null,
								date_on_sale_to_gmt: null,
								on_sale: false,
								status: 'publish',
								purchasable: true,
								virtual: false,
								downloadable: false,
								downloads: [],
								download_limit: 0,
								download_expiry: 0,
								tax_status: 'taxable',
								tax_class: '',
								manage_stock: false,
								stock_quantity: null,
								stock_status: 'instock',
								backorders: 'no',
								backorders_allowed: false,
								backordered: false,
								low_stock_amount: null,
								weight: '0.5',
								dimensions: {
									length: '24',
									width: '1',
									height: '2',
								},
								shipping_class: '',
								attributes: [
									{
										id: attributes.colorJSON.id,
										option: 'Red',
									},
								],
								menu_order: 0,
							},
						],
					},
				}
			);
			const vneckVariationsJSON = await vneckVariations.json();

			return {
				hoodieJSON,
				hoodieVariations: hoodieVariationsJSON.create,
				vneckJSON,
				vneckVariations: vneckVariationsJSON.create,
			};
		};

		const createSampleHierarchicalProducts = async () => {
			const parent = await request.post( '/wp-json/wc/v3/products', {
				data: {
					name: 'Parent Product oxo',
					date_created_gmt: '2021-09-27T15:50:19',
				},
			} );
			const parentJSON = await parent.json();

			const child = await request.post( '/wp-json/wc/v3/products', {
				data: {
					name: 'Child Product oxo',
					parent_id: parentJSON.id,
					date_created_gmt: '2021-09-28T15:50:19',
				},
			} );
			const childJSON = await child.json();

			return {
				parentJSON,
				childJSON,
			};
		};

		const createSampleProductReviews = async ( simpleProducts ) => {
			const cap = simpleProducts.find( ( p ) => p.name === 'Cap oxo' );

			const shirt = simpleProducts.find(
				( p ) => p.name === 'T-Shirt oxo'
			);

			const sunglasses = simpleProducts.find(
				( p ) => p.name === 'Sunglasses oxo'
			);

			const review1 = await request.post(
				'/wp-json/wc/v3/products/reviews',
				{
					data: {
						product_id: cap.id,
						rating: 3,
						review: 'Decent cap.',
						reviewer: 'John Doe',
						reviewer_email: 'john.doe@example.com',
					},
				}
			);
			const review1JSON = await review1.json();

			// We need to update the review in order for the product's
			// average_rating to be recalculated.
			// See: https://github.com/woocommerce/woocommerce/issues/29906.
			//await updateProductReview(review1.id);
			await request.post(
				`/wp-json/wc/v3/products/reviews/${ review1JSON.id }`,
				{
					data: {},
				}
			);

			const review2 = await request.post(
				'/wp-json/wc/v3/products/reviews',
				{
					data: {
						product_id: shirt.id,
						rating: 5,
						review: 'The BEST shirt ever!!',
						reviewer: 'Shannon Smith',
						reviewer_email: 'shannon.smith@example.com',
					},
				}
			);
			const review2JSON = await review2.json();

			//await updateProductReview(review2.id);
			await request.post(
				`/wp-json/wc/v3/products/reviews/${ review2JSON.id }`,
				{
					data: {},
				}
			);

			const review3 = await request.post(
				'/wp-json/wc/v3/products/reviews',
				{
					data: {
						product_id: sunglasses.id,
						rating: 1,
						review: 'These are way too expensive.',
						reviewer: 'Tim Frugalman',
						reviewer_email: 'timmyfrufru@example.com',
					},
				}
			);
			const review3JSON = await review3.json();

			await request.post(
				`/wp-json/wc/v3/products/reviews/${ review3JSON.id }`,
				{
					data: {},
				}
			);

			return [ review1JSON.id, review2JSON.id, review3JSON.id ];
		};

		const createSampleProductOrders = async ( simpleProducts ) => {
			const single = simpleProducts.find(
				( p ) => p.name === 'Single oxo'
			);
			const beanie = simpleProducts.find(
				( p ) => p.name === 'Beanie with Logo oxo'
			);
			const shirt = simpleProducts.find(
				( p ) => p.name === 'T-Shirt oxo'
			);

			const order1 = await request.post( '/wp-json/wc/v3/orders', {
				data: {
					set_paid: true,
					status: 'completed',
					line_items: [
						{
							product_id: single.id,
							quantity: 2,
						},
						{
							product_id: beanie.id,
							quantity: 3,
						},
						{
							product_id: shirt.id,
							quantity: 1,
						},
					],
				},
			} );
			const orderJSON = await order1.json();

			return [ orderJSON ];
		};

		const productsTestSetupCreateSampleData = async () => {
			const categories = await createSampleCategories();

			const attributes = await createSampleAttributes();

			const tags = await createSampleTags();

			const shippingClasses = await createSampleShippingClasses();

			const taxClasses = await createSampleTaxClasses();

			const simpleProducts = await createSampleSimpleProducts(
				categories,
				attributes,
				tags
			);

			const externalProducts = await createSampleExternalProducts(
				categories
			);

			const groupedProducts = await createSampleGroupedProduct(
				categories
			);

			const variableProducts = await createSampleVariableProducts(
				categories,
				attributes
			);

			const hierarchicalProducts =
				await createSampleHierarchicalProducts();

			const reviewIds = await createSampleProductReviews(
				simpleProducts
			);

			const orders = await createSampleProductOrders( simpleProducts );

			return {
				categories,
				attributes,
				tags,
				shippingClasses,
				taxClasses,
				simpleProducts,
				externalProducts,
				groupedProducts,
				variableProducts,
				hierarchicalProducts,
				reviewIds,
				orders,
			};
		};

		// create Sample Data function
		const createSampleData = async () => {
			const testProductData = await productsTestSetupCreateSampleData();
			const orderedProducts = {
				pocketHoodie: testProductData.simpleProducts.find(
					( p ) => p.name === 'Hoodie with Pocket oxo'
				),
				sunglasses: testProductData.simpleProducts.find(
					( p ) => p.name === 'Sunglasses oxo'
				),
				beanie: testProductData.simpleProducts.find(
					( p ) => p.name === 'Beanie oxo'
				),
				blueVneck:
					testProductData.variableProducts.vneckVariations.find(
						( p ) => p.sku === 'woo-vneck-tee-blue'
					),
				pennant: testProductData.externalProducts[ 0 ],
			};

			const johnAddress = {
				first_name: 'John',
				last_name: 'Doe',
				company: 'Automattic',
				country: 'US',
				address_1: '60 29th Street',
				address_2: '#343',
				city: 'San Francisco',
				state: 'CA',
				postcode: '94110',
				phone: '123456789',
			};
			const tinaAddress = {
				first_name: 'Tina',
				last_name: 'Clark',
				company: 'Automattic',
				country: 'US',
				address_1: 'Oxford Ave',
				address_2: '',
				city: 'Buffalo',
				state: 'NY',
				postcode: '14201',
				phone: '123456789',
			};
			const guestShippingAddress = {
				first_name: 'Ano',
				last_name: 'Nymous',
				company: '',
				country: 'US',
				address_1: '0 Incognito St',
				address_2: '',
				city: 'Erie',
				state: 'PA',
				postcode: '16515',
				phone: '123456789',
			};
			const guestBillingAddress = {
				first_name: 'Ben',
				last_name: 'Efactor',
				company: '',
				country: 'US',
				address_1: '200 W University Avenue',
				address_2: '',
				city: 'Gainesville',
				state: 'FL',
				postcode: '32601',
				phone: '123456789',
				email: 'ben.efactor@email.net',
			};

			const john = await request.post( '/wp-json/wc/v3/customers', {
				data: {
					first_name: 'John',
					last_name: 'Doe',
					username: 'john.doe',
					email: 'john.doe@example.com',
					billing: {
						...johnAddress,
						email: 'john.doe@example.com',
					},
					shipping: johnAddress,
				},
			} );
			const johnJSON = await john.json();

			const tina = await request.post( '/wp-json/wc/v3/customers', {
				data: {
					first_name: 'Tina',
					last_name: 'Clark',
					username: 'tina.clark',
					email: 'tina.clark@example.com',
					billing: {
						...tinaAddress,
						email: 'tina.clark@example.com',
					},
					shipping: tinaAddress,
				},
			} );
			const tinaJSON = await tina.json();

			const orderBaseData = {
				payment_method: 'cod',
				payment_method_title: 'Cash on Delivery',
				status: 'processing',
				set_paid: false,
				currency: 'USD',
				customer_id: 0,
			};

			const orders = [];
			// Have "John" order all products.
			Object.values( orderedProducts ).forEach( async ( product ) => {
				const order2 = await request.post( '/wp-json/wc/v3/orders', {
					data: {
						...orderBaseData,
						customer_id: johnJSON.id,
						billing: {
							...johnAddress,
							email: 'john.doe@example.com',
						},
						shipping: johnAddress,
						line_items: [
							{
								product_id: product.id,
								quantity: 1,
							},
						],
					},
				} );
				const orderJSON = await order2.json();

				orders.push( orderJSON );
			} );

			// Have "Tina" order some sunglasses and make a child order.
			// This somewhat resembles a subscription renewal, but we're just testing the `parent` field.
			const order2 = await request.post( '/wp-json/wc/v3/orders', {
				data: {
					...orderBaseData,
					status: 'completed',
					set_paid: true,
					customer_id: tinaJSON.id,
					billing: {
						...tinaAddress,
						email: 'tina.clark@example.com',
					},
					shipping: tinaAddress,
					line_items: [
						{
							product_id: orderedProducts.sunglasses.id,
							quantity: 1,
						},
					],
				},
			} );
			const order2JSON = await order2.json();

			orders.push( order2JSON );

			// create child order by referencing a parent_id
			const order3 = await request.post( '/wp-json/wc/v3/orders', {
				data: {
					...orderBaseData,
					parent_id: order2JSON.id,
					customer_id: tinaJSON.id,
					billing: {
						...tinaAddress,
						email: 'tina.clark@example.com',
					},
					shipping: tinaAddress,
					line_items: [
						{
							product_id: orderedProducts.sunglasses.id,
							quantity: 1,
						},
					],
				},
			} );
			const order3JSON = await order3.json();

			orders.push( order3JSON );

			// Guest order.
			const guestOrder = await request.post( '/wp-json/wc/v3/orders', {
				data: {
					...orderBaseData,
					billing: guestBillingAddress,
					shipping: guestShippingAddress,
					line_items: [
						{
							product_id: orderedProducts.pennant.id,
							quantity: 2,
						},
						{
							product_id: orderedProducts.beanie.id,
							quantity: 1,
						},
					],
				},
			} );
			const guestOrderJSON = await guestOrder.json();

			// Create an order with all possible numerical fields (taxes, fees, refunds, etc).
			await request.put(
				'/wp-json/wc/v3/settings/general/woocommerce_calc_taxes',
				{
					data: {
						value: 'yes',
					},
				}
			);

			await request.post( '/wp-json/wc/v3/taxes', {
				data: {
					country: '*',
					state: '*',
					postcode: '*',
					city: '*',
					name: 'Tax',
					rate: '5.5',
					shipping: true,
				},
			} );

			const coupon = await request.post( '/wp-json/wc/v3/coupons', {
				data: {
					code: 'save5',
					amount: '5',
				},
			} );
			const couponJSON = await coupon.json();

			const order4 = await request.post( '/wp-json/wc/v3/orders', {
				data: {
					...orderBaseData,
					line_items: [
						{
							product_id: orderedProducts.blueVneck.id,
							quantity: 1,
						},
					],
					coupon_lines: [
						{
							code: 'save5',
						},
					],
					shipping_lines: [
						{
							method_id: 'flat_rate',
							total: '5.00',
						},
					],
					fee_lines: [
						{
							total: '1.00',
							name: 'Test Fee',
						},
					],
				},
			} );
			const order4JSON = await order4.json();

			await request.post(
				`/wp-json/wc/v3/orders/${ order4JSON.id }/refunds`,
				{
					data: {
						api_refund: false, // Prevent an actual refund request (fails with CoD),
						line_items: [
							{
								id: order4JSON.line_items[ 0 ].id,
								quantity: 1,
								refund_total: order4JSON.line_items[ 0 ].total,
								refund_tax: [
									{
										id: order4JSON.line_items[ 0 ]
											.taxes[ 0 ].id,
										refund_total:
											order4JSON.line_items[ 0 ]
												.total_tax,
									},
								],
							},
						],
					},
				}
			);
			orders.push( order4JSON );

			return {
				customers: {
					johnJSON,
					tinaJSON,
				},
				orders,
				precisionOrder: order4JSON,
				hierarchicalOrders: {
					parent: order2JSON,
					child: order3JSON,
				},
				guestOrderJSON,
				testProductData,
				couponJSON,
			};
		};

		sampleData = await createSampleData();
	}, 100000 );

	test.afterAll( async ( { request } ) => {
		const productsTestSetupDeleteSampleData = async ( _sampleData ) => {
			const {
				categories,
				attributes,
				tags,
				shippingClasses,
				taxClasses,
				simpleProducts,
				externalProducts,
				groupedProducts,
				variableProducts,
				hierarchicalProducts,
				orders,
			} = _sampleData;

			const productIds = []
				.concat( simpleProducts.map( ( p ) => p.id ) )
				.concat( externalProducts.map( ( p ) => p.id ) )
				.concat( groupedProducts.map( ( p ) => p.id ) )
				.concat( [
					variableProducts.hoodieJSON.id,
					variableProducts.vneckJSON.id,
				] )
				.concat( [
					hierarchicalProducts.parentJSON.id,
					hierarchicalProducts.childJSON.id,
				] );

			for ( const _order of orders ) {
				await request.delete( `/wp-json/wc/v3/orders/${ _order.id }`, {
					data: {
						force: true,
					},
				} );
			}

			for ( const productId of productIds ) {
				await request.delete(
					`/wp-json/wc/v3/products/${ productId }`,
					{
						data: {
							force: true,
						},
					}
				);
			}

			await request.delete(
				`/wp-json/wc/v3/products/attributes/${ attributes.colorJSON.id }`,
				{
					data: {
						force: true,
					},
				}
			);

			await request.delete(
				`/wp-json/wc/v3/products/attributes/${ attributes.sizeJSON.id }`,
				{
					data: {
						force: true,
					},
				}
			);

			for ( const category of Object.values( categories ) ) {
				await request.delete(
					`/wp-json/wc/v3/products/categories/${ category.id }`,
					{
						data: {
							force: true,
						},
					}
				);
			}

			for ( const tag of Object.values( tags ) ) {
				await request.delete(
					`/wp-json/wc/v3/products/tags/${ tag.id }`,
					{
						data: {
							force: true,
						},
					}
				);
			}

			for ( const shippingClass of Object.values( shippingClasses ) ) {
				await request.delete(
					`/wp-json/wc/v3/products/shipping_classes/${ shippingClass.id }`,
					{
						data: {
							force: true,
						},
					}
				);
			}

			for ( const taxClass of Object.values( taxClasses ) ) {
				await request.delete(
					`/wp-json/wc/v3/taxes/classes/${ taxClass.slug }`,
					{
						data: {
							force: true,
						},
					}
				);
			}
		};

		const deleteSampleData = async ( _sampleData ) => {
			await productsTestSetupDeleteSampleData(
				_sampleData.testProductData
			);

			for ( const _order of _sampleData.orders.concat( [
				_sampleData.guestOrderJSON,
			] ) ) {
				await request.delete( `/wp-json/wc/v3/orders/${ _order.id }`, {
					data: {
						force: true,
					},
				} );
			}

			for ( const customer of Object.values( _sampleData.customers ) ) {
				await request.delete(
					`/wp-json/wc/v3/customers/${ customer.id }`,
					{
						data: {
							force: true,
						},
					}
				);
			}
		};

		await deleteSampleData( sampleData );
	}, 10000 );

	test( 'can create an order', async ( { request } ) => {
		const response = await request.post( '/wp-json/wc/v3/orders', {
			data: order,
		} );
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 201 );
		expect( responseJSON.id ).toBeDefined();
		orderId = responseJSON.id;

		// Validate the data type and verify the order is in a pending state
		expect( typeof responseJSON.status ).toBe( 'string' );
		expect( responseJSON.status ).toEqual( 'pending' );
	} );

	test( 'can retrieve an order', async ( { request } ) => {
		const response = await request.get(
			`/wp-json/wc/v3/orders/${ orderId }`
		);
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( orderId );
	} );

	test( 'can add shipping and billing contacts to an order', async ( {
		request,
	} ) => {
		// Update the billing and shipping fields on the order
		order.billing = updatedCustomerBilling;
		order.shipping = updatedCustomerShipping;

		const response = await request.put(
			`/wp-json/wc/v3/orders/${ orderId }`,
			{
				data: order,
			}
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );

		expect( responseJSON.billing ).toEqual( updatedCustomerBilling );
		expect( responseJSON.shipping ).toEqual( updatedCustomerShipping );
	} );

	test( 'can permanently delete an order', async ( { request } ) => {
		const response = await request.delete(
			`/wp-json/wc/v3/orders/${ orderId }`,
			{
				data: {
					force: true,
				},
			}
		);
		expect( response.status() ).toEqual( 200 );

		const getOrderResponse = await request.get(
			`/wp-json/wc/v3/orders/${ orderId }`
		);
		expect( getOrderResponse.status() ).toEqual( 404 );
	} );

	test.describe( 'List all orders', () => {
		const ORDERS_COUNT = 10;

		test( 'pagination', async ( { request } ) => {
			const pageSize = 4;
			const page1 = await request.get( '/wp-json/wc/v3/orders', {
				params: {
					per_page: pageSize,
					search: 'oxo',
				},
			} );
			const page1JSON = await page1.json();

			const page2 = await request.get( '/wp-json/wc/v3/orders', {
				params: {
					per_page: pageSize,
					page: 2,
					search: 'oxo',
				},
			} );
			const page2JSON = await page2.json();

			expect( page1.status() ).toEqual( 200 );
			expect( page2.status() ).toEqual( 200 );

			// Verify total page count.
			expect( page1.headers()[ 'x-wp-total' ] ).toEqual(
				ORDERS_COUNT.toString()
			);
			expect( page1.headers()[ 'x-wp-totalpages' ] ).toEqual( '3' );

			// Verify we get pageSize'd arrays.
			expect( Array.isArray( page1JSON ) ).toBe( true );
			expect( Array.isArray( page2JSON ) ).toBe( true );
			expect( page1JSON ).toHaveLength( pageSize );
			expect( page2JSON ).toHaveLength( pageSize );

			// Ensure all of the order IDs are unique (no page overlap).
			const allOrderIds = page1JSON
				.concat( page2JSON )
				.reduce( ( acc, { id } ) => {
					acc[ id ] = 1;
					return acc;
				}, {} );
			expect( Object.keys( allOrderIds ) ).toHaveLength( pageSize * 2 );

			// Verify that offset takes precedent over page number.
			const page2Offset = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					per_page: pageSize,
					page: 2,
					offset: pageSize + 1,
					search: 'oxo',
				},
			} );
			const page2OffsetJSON = await page2Offset.json();

			// The offset pushes the result set 1 order past the start of page 2.
			expect( page2OffsetJSON ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( {
						id: page2JSON[ 0 ].id,
					} ),
				] )
			);
			expect( page2OffsetJSON[ 0 ].id ).toEqual( page2JSON[ 1 ].id );

			// Verify the last page only has 1 order as we expect.
			const lastPage = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					per_page: pageSize,
					page: 3,
					search: 'oxo',
				},
			} );
			const lastPageJSON = await lastPage.json();

			expect( Array.isArray( lastPageJSON ) ).toBe( true );
			expect( lastPageJSON ).toHaveLength( 2 );

			// Verify a page outside the total page count is empty.
			const page6 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					page: 6,
					search: 'oxo',
				},
			} );
			const page6JSON = await page6.json();

			expect( Array.isArray( page6JSON ) ).toBe( true );
			expect( page6JSON ).toHaveLength( 0 );
		} );

		test( 'inclusion / exclusion', async ( { request } ) => {
			const allOrders = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					per_page: 10,
					search: 'oxo',
				},
			} );
			const allOrdersJSON = await allOrders.json();

			expect( allOrders.status() ).toEqual( 200 );
			const allOrdersIds = allOrdersJSON.map( ( _order ) => _order.id );
			expect( allOrdersIds ).toHaveLength( ORDERS_COUNT );

			const ordersToFilter = [
				allOrdersIds[ 0 ],
				allOrdersIds[ 2 ],
				allOrdersIds[ 4 ],
				allOrdersIds[ 7 ],
			];

			const included = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					per_page: 20,
					include: ordersToFilter.join( ',' ),
				},
			} );
			const includedJSON = await included.json();

			expect( included.status() ).toEqual( 200 );
			expect( includedJSON ).toHaveLength( ordersToFilter.length );
			expect( includedJSON ).toEqual(
				expect.arrayContaining(
					ordersToFilter.map( ( id ) =>
						expect.objectContaining( {
							id,
						} )
					)
				)
			);

			const excluded = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					per_page: 20,
					exclude: ordersToFilter.join( ',' ),
				},
			} );
			const excludedJSON = await excluded.json();

			expect( excluded.status() ).toEqual( 200 );
			expect( excludedJSON.length ).toBeGreaterThanOrEqual(
				Number( ORDERS_COUNT - ordersToFilter.length )
			);
			expect( excludedJSON ).toEqual(
				expect.not.arrayContaining(
					ordersToFilter.map( ( id ) =>
						expect.objectContaining( {
							id,
						} )
					)
				)
			);
		} );

		test( 'parent', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					parent: sampleData.hierarchicalOrders.parent.id,
				},
			} );
			const result1JSON = await result1.json();

			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 1 );
			expect( result1JSON[ 0 ].id ).toBe(
				sampleData.hierarchicalOrders.child.id
			);

			const result2 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					parent_exclude: sampleData.hierarchicalOrders.parent.id,
				},
			} );
			const result2JSON = await result2.json();

			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( {
						id: sampleData.hierarchicalOrders.child.id,
					} ),
				] )
			);
		} );

		test( 'status', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					status: 'completed',
					search: 'oxo',
				},
			} );
			const result1JSON = await result1.json();

			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 2 );
			expect( result1JSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						status: 'completed',
						customer_id: 0,
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'Single oxo',
								quantity: 2,
							} ),
							expect.objectContaining( {
								name: 'Beanie with Logo oxo',
								quantity: 3,
							} ),
							expect.objectContaining( {
								name: 'T-Shirt oxo',
								quantity: 1,
							} ),
						] ),
					} ),
					expect.objectContaining( {
						status: 'completed',
						customer_id: sampleData.customers.tinaJSON.id,
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'Sunglasses oxo',
								quantity: 1,
							} ),
						] ),
					} ),
				] )
			);

			const result2 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					status: 'processing',
					search: 'oxo',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 8 );
			expect( result2JSON ).toEqual(
				expect.not.arrayContaining(
					result1JSON.map( ( { id } ) =>
						expect.objectContaining( {
							id,
						} )
					)
				)
			);
		} );

		test( 'customer', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					customer: sampleData.customers.johnJSON.id,
				},
			} );
			const result1JSON = await result1.json();

			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 5 );
			result1JSON.forEach( ( _order ) =>
				expect( _order ).toEqual(
					expect.objectContaining( {
						customer_id: sampleData.customers.johnJSON.id,
					} )
				)
			);

			const result2 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					customer: 0,
					search: 'oxo',
				},
			} );
			const result2JSON = await result2.json();

			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 3 );
			result2JSON.forEach( ( _order ) =>
				expect( _order ).toEqual(
					expect.objectContaining( {
						customer_id: 0,
					} )
				)
			);
		} );

		test( 'product', async ( { request } ) => {
			const beanie = sampleData.testProductData.simpleProducts.find(
				( p ) => p.name === 'Beanie oxo'
			);
			const result1 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					product: beanie.id,
				},
			} );
			const result1JSON = await result1.json();

			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 2 );
			result1JSON.forEach( ( _order ) =>
				expect( _order ).toEqual(
					expect.objectContaining( {
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'Beanie oxo',
							} ),
						] ),
					} )
				)
			);
		} );

		// NOTE: This does not verify the `taxes` array nested in line items.
		// While the precision parameter doesn't affect those values, after some
		// discussion it seems `dp` may not be supported in v4 of the API.
		test( 'dp (precision)', async ( { request } ) => {
			const expectPrecisionToMatch = ( value, dp ) => {
				expect( value ).toEqual(
					Number.parseFloat( value ).toFixed( dp )
				);
			};

			const verifyOrderPrecision = ( _order, dp ) => {
				expectPrecisionToMatch( _order.discount_total, dp );
				expectPrecisionToMatch( _order.discount_tax, dp );
				expectPrecisionToMatch( _order.shipping_total, dp );
				expectPrecisionToMatch( _order.shipping_tax, dp );
				expectPrecisionToMatch( _order.cart_tax, dp );
				expectPrecisionToMatch( _order.total, dp );
				expectPrecisionToMatch( _order.total_tax, dp );

				_order.line_items.forEach( ( lineItem ) => {
					expectPrecisionToMatch( lineItem.total, dp );
					expectPrecisionToMatch( lineItem.total_tax, dp );
				} );

				_order.tax_lines.forEach( ( taxLine ) => {
					expectPrecisionToMatch( taxLine.tax_total, dp );
					expectPrecisionToMatch( taxLine.shipping_tax_total, dp );
				} );

				_order.shipping_lines.forEach( ( shippingLine ) => {
					expectPrecisionToMatch( shippingLine.total, dp );
					expectPrecisionToMatch( shippingLine.total_tax, dp );
				} );

				_order.fee_lines.forEach( ( feeLine ) => {
					expectPrecisionToMatch( feeLine.total, dp );
					expectPrecisionToMatch( feeLine.total_tax, dp );
				} );

				_order.refunds.forEach( ( refund ) => {
					expectPrecisionToMatch( refund.total, dp );
				} );
			};

			const result1 = await request.get(
				`wp-json/wc/v3/orders/${ sampleData.precisionOrder.id }`,
				{
					params: {
						dp: 1,
					},
				}
			);
			const result1JSON = await result1.json();

			expect( result1.status() ).toEqual( 200 );
			verifyOrderPrecision( result1JSON, 1 );

			const result2 = await request.get(
				`wp-json/wc/v3/orders/${ sampleData.precisionOrder.id }`,
				{
					params: {
						dp: 3,
					},
				}
			);
			const result2JSON = await result2.json();

			expect( result2.status() ).toEqual( 200 );
			verifyOrderPrecision( result2JSON, 3 );

			const result3 = await request.get(
				`wp-json/wc/v3/orders/${ sampleData.precisionOrder.id }`
			);
			const result3JSON = await result3.json();

			expect( result3.status() ).toEqual( 200 );
			verifyOrderPrecision( result3JSON, 2 ); // The default value for 'dp' is 2.
		} );

		test( 'search', async ( { request } ) => {
			// By default, 'search' looks in:
			// - _billing_address_index
			// - _shipping_address_index
			// - _billing_last_name
			// - _billing_email
			// - order_item_name

			// Test billing email.
			const result1 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					search: 'example.com',
				},
			} );
			const result1JSON = await result1.json();

			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON.length ).toBeGreaterThanOrEqual( 1 );
			result1JSON.forEach( ( _order ) =>
				expect( _order.billing.email ).toContain( 'example.com' )
			);

			// Test billing address.
			const result2 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					search: 'gainesville',
				},
			} );
			const result2JSON = await result2.json();

			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 1 );
			expect( result2JSON[ 0 ].id ).toEqual(
				sampleData.guestOrderJSON.id
			);

			// Test shipping address.
			const result3 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					search: 'Incognito',
				},
			} );
			const result3JSON = await result3.json();

			expect( result3.status() ).toEqual( 200 );
			expect( result3JSON ).toHaveLength( 1 );
			expect( result3JSON[ 0 ].id ).toEqual(
				sampleData.guestOrderJSON.id
			);

			// Test billing last name.
			const result4 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					search: 'Doe',
				},
			} );
			const result4JSON = await result4.json();

			expect( result4.status() ).toEqual( 200 );
			expect( result4JSON.length ).toBeGreaterThanOrEqual( 1 );
			result4JSON.forEach( ( _order ) =>
				expect( _order.billing.last_name ).toEqual( 'Doe' )
			);

			// Test order item name.
			const result5 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					search: 'Pennant oxo',
				},
			} );
			const result5JSON = await result5.json();

			expect( result5.status() ).toEqual( 200 );
			expect( result5JSON ).toHaveLength( 2 );
			result5JSON.forEach( ( _order ) =>
				expect( _order ).toEqual(
					expect.objectContaining( {
						line_items: expect.arrayContaining( [
							expect.objectContaining( {
								name: 'WordPress Pennant oxo',
							} ),
						] ),
					} )
				)
			);
		} );
	} );

	test.describe( 'orderby', () => {
		// The orders endpoint `orderby` parameter uses WP_Query, so our tests won't
		// include slug and title, since they are programmatically generated.
		test( 'default', async ( { request } ) => {
			// Default = date desc.
			const result = await request.get( 'wp-json/wc/v3/orders' );
			const resultJSON = await result.json();
			expect( result.status() ).toEqual( 200 );

			// Verify all dates are in descending order.
			let lastDate = Date.now();
			resultJSON.forEach( ( { date_created } ) => {
				const created = Date.parse( date_created + '.000Z' );
				expect( lastDate ).toBeGreaterThanOrEqual( created );
				lastDate = created;
			} );
		} );

		test( 'date', async ( { request } ) => {
			const result = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					order: 'asc',
					orderby: 'date',
				},
			} );
			const resultJSON = await result.json();

			expect( result.status() ).toEqual( 200 );

			// Verify all dates are in ascending order.
			let lastDate = 0;
			resultJSON.forEach( ( { date_created } ) => {
				const created = Date.parse( date_created + '.000Z' );
				expect( created ).toBeGreaterThanOrEqual( lastDate );
				lastDate = created;
			} );
		} );

		test( 'id', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					order: 'asc',
					orderby: 'id',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );

			// Verify all results are in ascending order.
			let lastId = 0;
			result1JSON.forEach( ( { id } ) => {
				expect( id ).toBeGreaterThan( lastId );
				lastId = id;
			} );

			const result2 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					order: 'desc',
					orderby: 'id',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );

			// Verify all results are in descending order.
			lastId = Number.MAX_SAFE_INTEGER;
			result2JSON.forEach( ( { id } ) => {
				expect( lastId ).toBeGreaterThan( id );
				lastId = id;
			} );
		} );

		test( 'include', async ( { request } ) => {
			const includeIds = [
				sampleData.precisionOrder.id,
				sampleData.hierarchicalOrders.parent.id,
				sampleData.guestOrderJSON.id,
			];

			const result1 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					order: 'asc',
					orderby: 'include',
					include: includeIds.join( ',' ),
				},
			} );
			const result1JSON = await result1.json();

			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( includeIds.length );

			// Verify all results are in proper order.
			result1JSON.forEach( ( { id }, idx ) => {
				expect( id ).toBe( includeIds[ idx ] );
			} );

			const result2 = await request.get( 'wp-json/wc/v3/orders', {
				params: {
					order: 'desc',
					orderby: 'include',
					include: includeIds.join( ',' ),
				},
			} );
			const result2JSON = await result2.json();

			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( includeIds.length );

			// Verify all results are in proper order.
			result2JSON.forEach( ( { id }, idx ) => {
				expect( id ).toBe( includeIds[ idx ] );
			} );
		} );
	} );
} );
