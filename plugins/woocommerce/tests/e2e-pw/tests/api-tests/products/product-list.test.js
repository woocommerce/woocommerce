const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );

test.describe( 'Products API tests: List All Products', () => {
	const PRODUCTS_COUNT = 20;
	let sampleData;

	test.beforeAll( async ( { request } ) => {
		const createSampleCategories = async () => {
			const clothing = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Clothingxxx',
					},
				}
			);
			const clothingJSON = await clothing.json();

			const accessories = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Accessoriesxxx',
						parent: clothingJSON.id,
					},
				}
			);
			const accessoriesJSON = await accessories.json();
			const hoodies = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Hoodiesxxx',
						parent: clothingJSON.id,
					},
				}
			);
			const hoodiesJSON = await hoodies.json();
			const tshirts = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Tshirtsxxx',
						parent: clothingJSON.id,
					},
				}
			);
			const tshirtsJSON = await tshirts.json();

			const decor = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Decorxxx',
					},
				}
			);
			const decorJSON = await decor.json();

			const music = await request.post(
				'/wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Musicxxx',
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
			//const { body: color } = await createProductAttribute( 'Color' );
			const color = await request.post(
				'/wp-json/wc/v3/products/attributes',
				{
					data: {
						name: 'Colorxxx',
					},
				}
			);
			const colorJSON = await color.json();

			//const { body: size } = await createProductAttribute( 'Size' );
			const size = await request.post(
				'/wp-json/wc/v3/products/attributes',
				{
					data: {
						name: 'Sizexxx',
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
			//const { body: cool } = await createProductTag( 'Cool' );
			const cool = await request.post( '/wp-json/wc/v3/products/tags', {
				data: {
					name: 'Coolxxx',
				},
			} );
			const coolJSON = await cool.json();

			return {
				coolJSON,
			};
		};

		const createSampleShippingClasses = async () => {
			//const { body: freight } = await createShippingClass( 'Freight' );
			const freight = await request.post(
				'/wp-json/wc/v3/products/shipping_classes',
				{
					data: {
						name: 'Freightxxx',
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

			//const { body: simpleProducts } = await createProducts( [
			const simpleProducts = await request.post(
				'/wp-json/wc/v3/products/batch',
				{
					data: {
						create: [
							{
								name: 'Beanie with Logo xxx',
								date_created_gmt: '2021-09-01T15:50:20',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'Woo-beanie-logo-product',
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
								name: 'T-Shirt with Logo xxx',
								date_created_gmt: '2021-09-02T15:50:20',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'Woo-tshirt-logo-product',
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
								name: 'Single xxx',
								date_created_gmt: '2021-09-03T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple, virtual product.</p>\n',
								sku: 'woo-single-product',
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
								name: 'Album xxx',
								date_created_gmt: '2021-09-04T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple, virtual product.</p>\n',
								sku: 'woo-album-product',
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
								name: 'Polo xxx',
								date_created_gmt: '2021-09-05T15:50:19',
								type: 'simple',
								status: 'pending',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-polo-product',
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
								name: 'Long Sleeve Tee xxx',
								date_created_gmt: '2021-09-06T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-long-sleeve-tee-product',
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
								shipping_class: 'freightxxx',
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
								name: 'Hoodie with Zipper xxx',
								date_created_gmt: '2021-09-07T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-hoodie-with-zipper-product',
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
								name: 'Hoodie with Pocket xxx',
								date_created_gmt: '2021-09-08T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'hidden',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-hoodie-with-pocket-product',
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
								name: 'Sunglasses xxx',
								date_created_gmt: '2021-09-09T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-sunglasses-product',
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
								name: 'Cap xxx',
								date_created_gmt: '2021-09-10T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: true,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-cap-product',
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
								name: 'Belt xxx',
								date_created_gmt: '2021-09-12T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-belt-product',
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
								name: 'Beanie xxx',
								date_created_gmt: '2021-09-13T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-beanie-product',
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
								name: 'T-Shirt xxx',
								date_created_gmt: '2021-09-14T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-tshirt-product',
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
								name: 'Hoodie with Logo xxx',
								date_created_gmt: '2021-09-15T15:50:19',
								type: 'simple',
								status: 'publish',
								featured: false,
								catalog_visibility: 'visible',
								description,
								short_description:
									'<p>This is a simple product.</p>\n',
								sku: 'woo-hoodie-with-logo-product',
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
								name: 'WordPress Pennant xxx',
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
								sku: 'wp-pennant-product',
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
								name: 'Logo Collection xxx',
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
								sku: 'logo-collection-product',
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
					name: 'Hoodie xxx',
					date_created_gmt: '2021-09-18T15:50:19',
					type: 'variable',
					status: 'publish',
					featured: false,
					catalog_visibility: 'visible',
					description,
					short_description: '<p>This is a variable product.</p>\n',
					sku: 'woo-hoodie-product',
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
								sku: 'woo-hoodie-blue-logo-product',
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
								sku: 'woo-hoodie-blue-product',
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
								sku: 'woo-hoodie-green-product',
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
								sku: 'woo-hoodie-red-product',
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
					name: 'V-Neck T-Shirt xxx',
					date_created_gmt: '2021-09-23T15:50:19',
					type: 'variable',
					status: 'publish',
					featured: true,
					catalog_visibility: 'visible',
					description,
					short_description: '<p>This is a variable product.</p>\n',
					sku: 'woo-vneck-tee-product',
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
								sku: 'woo-vneck-tee-blue-product',
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
								sku: 'woo-vneck-tee-green-product',
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
								sku: 'woo-vneck-tee-red-product',
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
					name: 'Parent Product xxx',
					date_created_gmt: '2021-09-27T15:50:19',
				},
			} );
			const parentJSON = await parent.json();

			const child = await request.post( '/wp-json/wc/v3/products', {
				data: {
					name: 'Child Product xxx',
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
			const cap = simpleProducts.find( ( p ) => p.name === 'Cap xxx' );
			const shirt = simpleProducts.find(
				( p ) => p.name === 'T-Shirt xxx'
			);
			const sunglasses = simpleProducts.find(
				( p ) => p.name === 'Sunglasses xxx'
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
				( p ) => p.name === 'Single xxx'
			);
			const beanie = simpleProducts.find(
				( p ) => p.name === 'Beanie with Logo xxx'
			);
			const shirt = simpleProducts.find(
				( p ) => p.name === 'T-Shirt xxx'
			);

			const order = await request.post( '/wp-json/wc/v3/orders', {
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
			const orderJSON = await order.json();
			return [ orderJSON ];
		};

		const createSampleData = async () => {
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

		sampleData = await createSampleData();
	}, 10000 );

	test.afterAll( async ( { request } ) => {
		const deleteSampleData = async ( _sampleData ) => {
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

			for ( const order of orders ) {
				await request.delete( `/wp-json/wc/v3/orders/${ order.id }`, {
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
				//await deleteRequest( `products/categories/${ id }`, true );
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

		await deleteSampleData( sampleData );
	}, 10000 );

	test.describe( 'List all products', () => {
		test( 'defaults', async ( { request } ) => {
			const result = await request.get( 'wp-json/wc/v3/products', {
				params: {
					search: 'xxx',
				},
			} );

			expect( result.status() ).toEqual( 200 );
			expect( result.headers()[ 'x-wp-total' ] ).toEqual(
				PRODUCTS_COUNT.toString()
			);
			expect( result.headers()[ 'x-wp-totalpages' ] ).toEqual( '2' );
		} );

		test( 'pagination', async ( { request } ) => {
			const pageSize = 6;
			const page1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: pageSize,
					search: 'xxx',
				},
			} );
			const page1JSON = await page1.json();
			const page2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: pageSize,
					page: 2,
					search: 'xxx',
				},
			} );
			const page2JSON = await page2.json();
			expect( page1.status() ).toEqual( 200 );
			expect( page2.status() ).toEqual( 200 );

			// Verify total page count.
			expect( page1.headers()[ 'x-wp-total' ] ).toEqual(
				PRODUCTS_COUNT.toString()
			);
			expect( page1.headers()[ 'x-wp-totalpages' ] ).toEqual( '4' );

			// Verify we get pageSize'd arrays.
			expect( Array.isArray( page1JSON ) ).toBe( true );
			expect( Array.isArray( page2JSON ) ).toBe( true );
			expect( page1JSON ).toHaveLength( pageSize );
			expect( page2JSON ).toHaveLength( pageSize );

			// Ensure all of the product IDs are unique (no page overlap).
			const allProductIds = page1JSON
				.concat( page2JSON )
				.reduce( ( acc, product ) => {
					acc[ product.id ] = 1;
					return acc;
				}, {} );
			expect( Object.keys( allProductIds ) ).toHaveLength( pageSize * 2 );

			// Verify that offset takes precedent over page number.
			const page2Offset = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: pageSize,
					page: 2,
					offset: pageSize + 1,
					search: 'xxx',
				},
			} );
			const page2OffsetJSON = await page2Offset.json();
			// The offset pushes the result set 1 product past the start of page 2.
			expect( page2OffsetJSON ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( {
						id: page2JSON[ 0 ].id,
					} ),
				] )
			);
			expect( page2OffsetJSON[ 0 ].id ).toEqual( page2JSON[ 1 ].id );

			// Verify the last page only has 2 products as we expect.
			const lastPage = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: pageSize,
					page: 4,
					search: 'xxx',
				},
			} );
			const lastPageJSON = await lastPage.json();
			expect( Array.isArray( lastPageJSON ) ).toBe( true );
			expect( lastPageJSON ).toHaveLength( 2 );

			// Verify a page outside the total page count is empty.
			const page6 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: pageSize,
					page: 6,
					search: 'xxx',
				},
			} );
			const page6JSON = await page6.json();
			expect( Array.isArray( page6JSON ) ).toBe( true );
			expect( page6JSON ).toHaveLength( 0 );
		} );

		test( 'search', async ( { request } ) => {
			// Match in the short description.
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					search: 'external',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON.length ).toBeGreaterThanOrEqual( 1 );
			expect( result1JSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						name: 'WordPress Pennant xxx',
					} ),
				] )
			);

			// Match in the product name.
			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					search: 'pocket xxx',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 1 );
			expect( result2JSON[ 0 ].name ).toBe( 'Hoodie with Pocket xxx' );
		} );

		test( 'inclusion / exclusion', async ( { request } ) => {
			const allProducts = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: 20,
					search: 'xxx',
				},
			} );
			const allProductsJSON = await allProducts.json();
			expect( allProducts.status() ).toEqual( 200 );
			const allProductIds = allProductsJSON.map(
				( product ) => product.id
			);
			expect( allProductIds ).toHaveLength( PRODUCTS_COUNT );

			const productsToFilter = [
				allProductIds[ 2 ],
				allProductIds[ 4 ],
				allProductIds[ 7 ],
				allProductIds[ 13 ],
			];

			const included = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: 20,
					include: productsToFilter.join( ',' ),
				},
			} );
			const includedJSON = await included.json();
			expect( included.status() ).toEqual( 200 );
			expect( includedJSON ).toHaveLength( productsToFilter.length );
			expect( includedJSON ).toEqual(
				expect.arrayContaining(
					productsToFilter.map( ( id ) =>
						expect.objectContaining( {
							id,
						} )
					)
				)
			);

			const excluded = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: 20,
					exclude: productsToFilter.join( ',' ),
				},
			} );
			const excludedJSON = await excluded.json();
			expect( excluded.status() ).toEqual( 200 );
			expect( excludedJSON.length ).toBeGreaterThanOrEqual(
				Number( PRODUCTS_COUNT - productsToFilter.length )
			);
			expect( excludedJSON ).toEqual(
				expect.not.arrayContaining(
					productsToFilter.map( ( id ) =>
						expect.objectContaining( {
							id,
						} )
					)
				)
			);
		} );

		test( 'slug', async ( { request } ) => {
			// Match by slug.
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					slug: 't-shirt-with-logo-xxx',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 1 );
			expect( result1JSON[ 0 ].slug ).toBe( 't-shirt-with-logo-xxx' );

			// No matches
			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					slug: 'no-product-with-this-slug',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 0 );
		} );

		test( 'sku', async ( { request } ) => {
			// Match by SKU.
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					sku: 'woo-sunglasses-product',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 1 );
			expect( result1JSON[ 0 ].sku ).toBe( 'woo-sunglasses-product' );

			// No matches
			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					sku: 'no-product-with-this-sku',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 0 );
		} );

		test( 'type', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					type: 'simple',
					search: 'xxx',
				},
			} );
			expect( result1.status() ).toEqual( 200 );
			expect( result1.headers()[ 'x-wp-total' ] ).toEqual( '16' );

			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					type: 'external',
					search: 'xxx',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 1 );
			expect( result2JSON[ 0 ].name ).toBe( 'WordPress Pennant xxx' );

			const result3 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					type: 'variable',
					search: 'xxx',
				},
			} );
			const result3JSON = await result3.json();
			expect( result3.status() ).toEqual( 200 );
			expect( result3JSON ).toHaveLength( 2 );

			const result4 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					type: 'grouped',
					search: 'xxx',
				},
			} );
			const result4JSON = await result4.json();
			expect( result4.status() ).toEqual( 200 );
			expect( result4JSON ).toHaveLength( 1 );
			expect( result4JSON[ 0 ].name ).toBe( 'Logo Collection xxx' );
		} );

		test( 'featured', async ( { request } ) => {
			const featured = [
				expect.objectContaining( {
					name: 'Hoodie with Zipper xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie with Pocket xxx',
				} ),
				expect.objectContaining( {
					name: 'Sunglasses xxx',
				} ),
				expect.objectContaining( {
					name: 'Cap xxx',
				} ),
				expect.objectContaining( {
					name: 'V-Neck T-Shirt xxx',
				} ),
			];

			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					featured: true,
					search: 'xxx',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( featured.length );
			expect( result1JSON ).toEqual( expect.arrayContaining( featured ) );

			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					featured: false,
					search: 'xxx',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toEqual(
				expect.not.arrayContaining( featured )
			);
		} );

		test( 'categories', async ( { request } ) => {
			const accessory = [
				expect.objectContaining( {
					name: 'Beanie xxx',
				} ),
			];
			const hoodies = [
				expect.objectContaining( {
					name: 'Hoodie with Zipper xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie with Pocket xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie with Logo xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie xxx',
				} ),
			];

			// Verify that subcategories are included.
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					per_page: 20,
					category: sampleData.categories.clothingJSON.id,
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toEqual(
				expect.arrayContaining( accessory )
			);
			expect( result1JSON ).toEqual( expect.arrayContaining( hoodies ) );

			// Verify sibling categories are not.
			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					category: sampleData.categories.hoodiesJSON.id,
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toEqual(
				expect.not.arrayContaining( accessory )
			);
			expect( result2JSON ).toEqual( expect.arrayContaining( hoodies ) );
		} );

		test( 'on sale', async ( { request } ) => {
			const onSale = [
				expect.objectContaining( {
					name: 'Beanie with Logo xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie with Pocket xxx',
				} ),
				expect.objectContaining( {
					name: 'Single xxx',
				} ),
				expect.objectContaining( {
					name: 'Cap xxx',
				} ),
				expect.objectContaining( {
					name: 'Belt xxx',
				} ),
				expect.objectContaining( {
					name: 'Beanie xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie xxx',
				} ),
			];

			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					on_sale: true,
					search: 'xxx',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( onSale.length );
			expect( result1JSON ).toEqual( expect.arrayContaining( onSale ) );

			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					on_sale: false,
					search: 'xxx',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toEqual(
				expect.not.arrayContaining( onSale )
			);
		} );

		test( 'price', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					min_price: 21,
					max_price: 28,
					search: 'xxx',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 1 );
			expect( result1JSON[ 0 ].name ).toBe( 'Long Sleeve Tee xxx' );
			expect( result1JSON[ 0 ].price ).toBe( '25' );

			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					max_price: 5,
					search: 'xxx',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 1 );
			expect( result2JSON[ 0 ].name ).toBe( 'Single xxx' );
			expect( result2JSON[ 0 ].price ).toBe( '2' );

			const result3 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					min_price: 5,
					order: 'asc',
					orderby: 'price',
					search: 'xxx',
				},
			} );
			const result3JSON = await result3.json();
			expect( result3.status() ).toEqual( 200 );
			expect( result3JSON ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( {
						name: 'Single xxx',
					} ),
				] )
			);
		} );

		test( 'before / after', async ( { request } ) => {
			const before = [
				expect.objectContaining( {
					name: 'Album xxx',
				} ),
				expect.objectContaining( {
					name: 'Single xxx',
				} ),
				expect.objectContaining( {
					name: 'T-Shirt with Logo xxx',
				} ),
				expect.objectContaining( {
					name: 'Beanie with Logo xxx',
				} ),
			];
			const after = [
				expect.objectContaining( {
					name: 'Hoodie xxx',
				} ),
				expect.objectContaining( {
					name: 'V-Neck T-Shirt xxx',
				} ),
				expect.objectContaining( {
					name: 'Parent Product xxx',
				} ),
				expect.objectContaining( {
					name: 'Child Product xxx',
				} ),
			];

			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					before: '2021-09-05T15:50:19',
					search: 'xxx',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( before.length );
			expect( result1JSON ).toEqual( expect.arrayContaining( before ) );

			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					after: '2021-09-18T15:50:18',
					search: 'xxx',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toEqual(
				expect.not.arrayContaining( before )
			);
			expect( result2JSON ).toHaveLength( after.length );
			expect( result2JSON ).toEqual( expect.arrayContaining( after ) );
		} );

		test( 'attributes', async ( { request } ) => {
			const red = sampleData.attributes.colors.find(
				( term ) => term.name === 'Red'
			);

			const redProducts = [
				expect.objectContaining( {
					name: 'V-Neck T-Shirt xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie xxx',
				} ),
				expect.objectContaining( {
					name: 'Beanie xxx',
				} ),
				expect.objectContaining( {
					name: 'Beanie with Logo xxx',
				} ),
			];

			const result = await request.get( 'wp-json/wc/v3/products', {
				params: {
					attribute: 'pa_colorxxx',
					attribute_term: red.id,
				},
			} );
			const resultJSON = await result.json();

			expect( result.status() ).toEqual( 200 );
			expect( resultJSON ).toHaveLength( redProducts.length );
			expect( resultJSON ).toEqual(
				expect.arrayContaining( redProducts )
			);
		} );

		test( 'status', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					status: 'pending',
					search: 'xxx',
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 1 );
			expect( result1JSON[ 0 ].name ).toBe( 'Polo xxx' );

			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					status: 'draft',
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toHaveLength( 0 );
		} );

		test( 'shipping class', async ( { request } ) => {
			const result = await request.get( 'wp-json/wc/v3/products', {
				params: {
					shipping_class: sampleData.shippingClasses.freightJSON.id,
				},
			} );
			const resultJSON = await result.json();
			expect( result.status() ).toEqual( 200 );
			expect( resultJSON ).toHaveLength( 1 );
			expect( resultJSON[ 0 ].name ).toBe( 'Long Sleeve Tee xxx' );
		} );

		test( 'tax class', async ( { request } ) => {
			const result = await request.get( 'wp-json/wc/v3/products', {
				params: {
					tax_class: 'reduced-rate',
					search: 'xxx',
				},
			} );
			const resultJSON = await result.json();
			expect( result.status() ).toEqual( 200 );
			expect( resultJSON ).toHaveLength( 1 );
			expect( resultJSON[ 0 ].name ).toBe( 'Sunglasses xxx' );
		} );

		test( 'stock status', async ( { request } ) => {
			const result = await request.get( 'wp-json/wc/v3/products', {
				params: {
					stock_status: 'onbackorder',
					search: 'xxx',
				},
			} );
			const resultJSON = await result.json();
			expect( result.status() ).toEqual( 200 );
			expect( resultJSON ).toHaveLength( 1 );
			expect( resultJSON[ 0 ].name ).toBe( 'T-Shirt xxx' );
		} );

		test( 'tags', async ( { request } ) => {
			const coolProducts = [
				expect.objectContaining( {
					name: 'Sunglasses xxx',
				} ),
				expect.objectContaining( {
					name: 'Hoodie with Pocket xxx',
				} ),
				expect.objectContaining( {
					name: 'Beanie xxx',
				} ),
			];

			const result = await request.get( 'wp-json/wc/v3/products', {
				params: {
					tag: sampleData.tags.coolJSON.id,
				},
			} );
			const resultJSON = await result.json();

			expect( result.status() ).toEqual( 200 );
			expect( resultJSON ).toHaveLength( coolProducts.length );
			expect( resultJSON ).toEqual(
				expect.arrayContaining( coolProducts )
			);
		} );

		test( 'parent', async ( { request } ) => {
			const result1 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					parent: sampleData.hierarchicalProducts.parentJSON.id,
				},
			} );
			const result1JSON = await result1.json();
			expect( result1.status() ).toEqual( 200 );
			expect( result1JSON ).toHaveLength( 1 );
			expect( result1JSON[ 0 ].name ).toBe( 'Child Product xxx' );

			const result2 = await request.get( 'wp-json/wc/v3/products', {
				params: {
					parent_exclude:
						sampleData.hierarchicalProducts.parentJSON.id,
				},
			} );
			const result2JSON = await result2.json();
			expect( result2.status() ).toEqual( 200 );
			expect( result2JSON ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( {
						name: 'Child Product xxx',
					} ),
				] )
			);
		} );

		test.describe( 'orderby', () => {
			const productNamesAsc = [
				'Album xxx',
				'Beanie with Logo xxx',
				'Beanie xxx',
				'Belt xxx',
				'Cap xxx',
				'Child Product xxx',
				'Hoodie with Logo xxx',
				'Hoodie with Pocket xxx',
				'Hoodie with Zipper xxx',
				'Hoodie xxx',
				'Logo Collection xxx',
				'Long Sleeve Tee xxx',
				'Parent Product xxx',
				'Polo xxx',
				'Single xxx',
				'Sunglasses xxx',
				'T-Shirt with Logo xxx',
				'T-Shirt xxx',
				'V-Neck T-Shirt xxx',
				'WordPress Pennant xxx',
			];
			const productNamesDesc = [ ...productNamesAsc ].reverse();
			const productNamesByRatingAsc = [
				'Sunglasses xxx',
				'Cap xxx',
				'T-Shirt xxx',
			];
			const productNamesByRatingDesc = [
				...productNamesByRatingAsc,
			].reverse();
			const productNamesByPopularityDesc = [
				'Beanie with Logo xxx',
				'Single xxx',
				'T-Shirt xxx',
			];
			const productNamesByPopularityAsc = [
				...productNamesByPopularityDesc,
			].reverse();

			test( 'default', async ( { request } ) => {
				// Default = date desc.
				const result = await request.get( 'wp-json/wc/v3/products', {
					params: {
						search: 'xxx',
					},
				} );
				const resultJSON = await result.json();
				expect( result.status() ).toEqual( 200 );

				// Verify all dates are in descending order.
				let lastDate = Date.now();
				resultJSON.forEach( ( { date_created_gmt } ) => {
					const created = Date.parse( date_created_gmt + '.000Z' );
					expect( lastDate ).toBeGreaterThan( created );
					lastDate = created;
				} );
			} );

			test( 'date', async ( { request } ) => {
				const result = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'asc',
						orderby: 'date',
						search: 'xxx',
					},
				} );
				const resultJSON = await result.json();
				expect( result.status() ).toEqual( 200 );

				// Verify all dates are in ascending order.
				let lastDate = 0;
				resultJSON.forEach( ( { date_created_gmt } ) => {
					const created = Date.parse( date_created_gmt + '.000Z' );
					expect( created ).toBeGreaterThan( lastDate );
					lastDate = created;
				} );
			} );

			test( 'id', async ( { request } ) => {
				const result1 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'asc',
						orderby: 'id',
						search: 'xxx',
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

				const result2 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'desc',
						orderby: 'id',
						search: 'xxx',
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

			test( 'title', async ( { request } ) => {
				const result1 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'asc',
						orderby: 'title',
						per_page: productNamesAsc.length,
						search: 'xxx',
					},
				} );
				const result1JSON = await result1.json();
				expect( result1.status() ).toEqual( 200 );

				// Verify all results are in ascending order.
				result1JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesAsc[ idx ] );
				} );

				const result2 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'desc',
						orderby: 'title',
						per_page: productNamesDesc.length,
						search: 'xxx',
					},
				} );
				const result2JSON = await result2.json();
				expect( result2.status() ).toEqual( 200 );

				// Verify all results are in descending order.
				result2JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesDesc[ idx ] );
				} );
			} );

			test( 'slug orderby', async ( { request } ) => {
				const productNamesBySlugAsc = [
					'Polo xxx', // The Polo isn't published so it has an empty slug.
					...productNamesAsc.filter( ( p ) => p !== 'Polo xxx' ),
				];
				const productNamesBySlugDesc = [
					...productNamesBySlugAsc,
				].reverse();

				const result1 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'asc',
						orderby: 'slug',
						per_page: productNamesBySlugAsc.length,
						search: 'xxx',
					},
				} );
				const result1JSON = await result1.json();
				expect( result1.status() ).toEqual( 200 );

				// Verify all results are in ascending order.
				result1JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesBySlugAsc[ idx ] );
				} );

				const result2 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'desc',
						orderby: 'slug',
						per_page: productNamesBySlugDesc.length,
						search: 'xxx',
					},
				} );
				const result2JSON = await result2.json();
				expect( result2.status() ).toEqual( 200 );

				// Verify all results are in descending order.
				result2JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesBySlugDesc[ idx ] );
				} );
			} );

			test( 'price orderby', async ( { request } ) => {
				const productNamesMinPriceAsc = [
					'Parent Product xxx',
					'Child Product xxx',
					'Single xxx',
					'WordPress Pennant xxx',
					'Album xxx',
					'V-Neck T-Shirt xxx',
					'Cap xxx',
					'Beanie with Logo xxx',
					'T-Shirt with Logo xxx',
					'Beanie xxx',
					'T-Shirt xxx',
					'Logo Collection xxx',
					'Polo xxx',
					'Long Sleeve Tee xxx',
					'Hoodie with Pocket xxx',
					'Hoodie xxx',
					'Hoodie with Zipper xxx',
					'Hoodie with Logo xxx',
					'Belt xxx',
					'Sunglasses xxx',
				];
				const result1 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'asc',
						orderby: 'price',
						per_page: productNamesMinPriceAsc.length,
						search: 'xxx',
					},
				} );
				const result1JSON = await result1.json();
				expect( result1.status() ).toEqual( 200 );
				expect( result1JSON ).toHaveLength(
					productNamesMinPriceAsc.length
				);

				// Verify all results are in ascending order.
				// The query uses the min price calculated in the product meta lookup table,
				// so we can't just check the price property of the response.
				result1JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesMinPriceAsc[ idx ] );
				} );

				const productNamesMaxPriceDesc = [
					'Sunglasses xxx',
					'Belt xxx',
					'Hoodie xxx',
					'Logo Collection xxx',
					'Hoodie with Logo xxx',
					'Hoodie with Zipper xxx',
					'Hoodie with Pocket xxx',
					'Long Sleeve Tee xxx',
					'V-Neck T-Shirt xxx',
					'Polo xxx',
					'T-Shirt xxx',
					'Beanie xxx',
					'T-Shirt with Logo xxx',
					'Beanie with Logo xxx',
					'Cap xxx',
					'Album xxx',
					'WordPress Pennant xxx',
					'Single xxx',
					'Child Product xxx',
					'Parent Product xxx',
				];

				const result2 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'desc',
						orderby: 'price',
						per_page: productNamesMaxPriceDesc.length,
						search: 'xxx',
					},
				} );
				const result2JSON = await result2.json();
				expect( result2.status() ).toEqual( 200 );
				expect( result2JSON ).toHaveLength(
					productNamesMaxPriceDesc.length
				);

				// Verify all results are in descending order.
				// The query uses the max price calculated in the product meta lookup table,
				// so we can't just check the price property of the response.
				result2JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesMaxPriceDesc[ idx ] );
				} );
			} );

			test( 'include', async ( { request } ) => {
				const includeIds = [
					sampleData.groupedProducts[ 0 ].id,
					sampleData.simpleProducts[ 3 ].id,
					sampleData.hierarchicalProducts.parentJSON.id,
				];

				const result1 = await request.get( 'wp-json/wc/v3/products', {
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

				const result2 = await request.get( 'wp-json/wc/v3/products', {
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

			test( 'rating (desc)', async ( { request } ) => {
				const result2 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'desc',
						orderby: 'rating',
						per_page: productNamesByRatingDesc.length,
						search: 'xxx',
					},
				} );
				const result2JSON = await result2.json();
				expect( result2.status() ).toEqual( 200 );

				// Verify all results are in descending order.
				result2JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByRatingDesc[ idx ] );
				} );
			} );

			// This case will remain skipped until ratings can be sorted ascending.
			// See: https://github.com/woocommerce/woocommerce/issues/30354#issuecomment-925955099.
			test.skip( 'rating (asc)', async ( { request } ) => {
				const result1 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'asc',
						orderby: 'rating',
						per_page: productNamesByRatingAsc.length,
						search: 'xxx',
					},
				} );
				expect( result1.status() ).toEqual( 200 );
				const result1JSON = await result1.json();

				// Verify all results are in ascending order.
				result1JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByRatingAsc[ idx ] );
				} );
			} );

			// This case will remain skipped until popularity can be sorted ascending.
			// See: https://github.com/woocommerce/woocommerce/issues/30354#issuecomment-925955099.
			test.skip( 'popularity (asc)', async ( { request } ) => {
				const result1 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'asc',
						orderby: 'popularity',
						per_page: productNamesByPopularityAsc.length,
						search: 'xxx',
					},
				} );
				const result1JSON = await result1.json();
				expect( result1.status() ).toEqual( 200 );

				// Verify all results are in ascending order.
				result1JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByPopularityAsc[ idx ] );
				} );
			} );

			test( 'popularity (desc)', async ( { request } ) => {
				const result2 = await request.get( 'wp-json/wc/v3/products', {
					params: {
						order: 'desc',
						orderby: 'popularity',
						per_page: productNamesByPopularityDesc.length,
						search: 'xxx',
					},
				} );
				const result2JSON = await result2.json();
				expect( result2.status() ).toEqual( 200 );

				// Verify all results are in descending order.
				result2JSON.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByPopularityDesc[ idx ] );
				} );
			} );
		} );
	} );
} );
