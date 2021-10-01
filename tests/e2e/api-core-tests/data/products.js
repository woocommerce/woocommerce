/**
 * Internal dependencies
 */
const {
	getRequest,
	postRequest,
	putRequest,
	deleteRequest,
} = require('../utils/request');

const getProducts = ( params = {} ) => getRequest( 'products', params );

const createProduct = ( data ) => postRequest( 'products', data );
const createProductVariations = ( parentId, variations ) => postRequest(
	`products/${ parentId }/variations/batch`,
	{
		create: variations,
	}
)
const createProducts = ( products ) => postRequest( 'products/batch', { create: products } );
const createProductCategory = ( data ) => postRequest( 'products/categories', data );
const createProductAttribute = ( name ) => postRequest( 'products/attributes', { name } );
const createProductAttributeTerms = ( parentId, termNames ) => postRequest(
	`products/attributes/${ parentId }/terms/batch`,
	{
		create: termNames.map( name => ( { name } ) )
	}
);
const createProductReview = ( productId, review ) => postRequest( 'products/reviews', {
	product_id: productId,
	...review,
} );
const updateProductReview = ( reviewId, data = {} ) => putRequest( `products/reviews/${ reviewId }`, data );
const createProductTag = ( name ) => postRequest( 'products/tags', { name } );
const createShippingClass = ( name ) => postRequest( 'products/shipping_classes', { name } );
const createTaxClass = ( name ) => postRequest( 'taxes/classes', { name } );

const createSampleCategories = async () => {
	const { body: clothing } = await createProductCategory( { name: 'Clothing' } );
	const { body: accessories } = await createProductCategory( { name: 'Accessories', parent: clothing.id } );
	const { body: hoodies } = await createProductCategory( { name: 'Hoodies', parent: clothing.id } );
	const { body: tshirts } = await createProductCategory( { name: 'Tshirts', parent: clothing.id } );
	const { body: decor } = await createProductCategory( { name: 'Decor' } );
	const { body: music } = await createProductCategory( { name: 'Music' } );

	return {
		clothing,
		accessories,
		hoodies,
		tshirts,
		decor,
		music,
	};
};

const createSampleAttributes = async () => {
	const { body: color } = await createProductAttribute( 'Color' );
	const { body: size } = await createProductAttribute( 'Size' );
	const { body: colors } = await createProductAttributeTerms( color.id, [ 'Blue', 'Gray', 'Green', 'Red', 'Yellow' ] );
	const { body: sizes } = await createProductAttributeTerms( size.id, [ 'Large', 'Medium', 'Small' ] );

	return {
		color,
		colors: colors.create,
		size,
		sizes: sizes.create,
	};
};

const createSampleTags = async () => {
	const { body: cool } = await createProductTag( 'Cool' );

	return {
		cool,
	};
}

const createSampleShippingClasses = async () => {
	const { body: freight } = await createShippingClass( 'Freight' );

	return {
		freight,
	};
}

const createSampleTaxClasses = async () => {
	const { body: reducedRate } = await createTaxClass( 'Reduced Rate' );

	return {
		reducedRate,
	};
}

const createSampleSimpleProducts = async ( categories, attributes, tags ) => {
	const description = '<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. '
		+ 'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. '
		+ 'Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n';

	const { body: simpleProducts } = await createProducts( [ 
		{ 
			name: 'Beanie with Logo',
			date_created_gmt: '2021-09-01T15:50:20',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '6', width: '4', height: '1' },
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
			categories: [ { id: categories.accessories.id } ],
			tags: [],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Red' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 62, 63, 61, 60 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'T-Shirt with Logo',
			date_created_gmt: '2021-09-02T15:50:20',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '10', width: '12', height: '0.5' },
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
			categories: [ { id: categories.tshirts.id } ],
			tags: [],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Gray' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 59, 67, 66, 56 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Single',
			date_created_gmt: '2021-09-03T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple, virtual product.</p>\n',
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
					file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg' 
				} 
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
			dimensions: { length: '', width: '', height: '' },
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
			categories: [ { id: categories.music.id } ],
			tags: [],
			attributes: [],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 68 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Album',
			date_created_gmt: '2021-09-04T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple, virtual product.</p>\n',
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
					file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg' 
				},
				{ 
					id: 'aea8ef69-ccdc-4d83-8e21-3c395ebb9411',
					name: 'Single 2',
					file: 'https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/album.jpg' 
				} 
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
			dimensions: { length: '', width: '', height: '' },
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
			categories: [ { id: categories.music.id } ],
			tags: [],
			attributes: [],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 69 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Polo',
			date_created_gmt: '2021-09-05T15:50:19',
			type: 'simple',
			status: 'pending',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '6', width: '5', height: '1' },
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
			categories: [ { id: categories.tshirts.id } ],
			tags: [],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Blue' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 59, 56, 66, 76 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Long Sleeve Tee',
			date_created_gmt: '2021-09-06T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '7', width: '5', height: '1' },
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
			categories: [ { id: categories.tshirts.id } ],
			tags: [],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Green' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 59, 56, 76, 67 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Hoodie with Zipper',
			date_created_gmt: '2021-09-07T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: true,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '8', width: '6', height: '2' },
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
			categories: [ { id: categories.hoodies.id } ],
			tags: [],
			attributes: [],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 57, 58 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Hoodie with Pocket',
			date_created_gmt: '2021-09-08T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: true,
			catalog_visibility: 'hidden',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '10', width: '8', height: '2' },
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
			categories: [ { id: categories.hoodies.id } ],
			tags: [ { id: tags.cool.id } ],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Gray' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 65, 57, 58 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Sunglasses',
			date_created_gmt: '2021-09-09T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: true,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '4', width: '1.4', height: '1' },
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
			categories: [ { id: categories.accessories.id } ],
			tags: [ { id: tags.cool.id } ],
			attributes: [],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 60, 62, 77, 61 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Cap',
			date_created_gmt: '2021-09-10T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: true,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '8', width: '6.5', height: '4' },
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
			categories: [ { id: categories.accessories.id } ],
			tags: [],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Yellow' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 60, 77, 61, 63 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Belt',
			date_created_gmt: '2021-09-12T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '12', width: '2', height: '1.5' },
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
			categories: [ { id: categories.accessories.id } ],
			tags: [],
			attributes: [],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 63, 77, 62, 60 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'Beanie',
			date_created_gmt: '2021-09-13T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '4', width: '5', height: '0.5' },
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
			categories: [ { id: categories.accessories.id } ],
			tags: [ { id: tags.cool.id } ],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Red' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 63, 62, 61, 77 ],
			stock_status: 'instock' 
		},
		{ 
			name: 'T-Shirt',
			date_created_gmt: '2021-09-14T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '8', width: '6', height: '1' },
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
			categories: [ { id: categories.tshirts.id } ],
			tags: [],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Gray' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 67, 76, 56, 66 ],
			stock_status: 'onbackorder' 
		},
		{ 
			name: 'Hoodie with Logo',
			date_created_gmt: '2021-09-15T15:50:19',
			type: 'simple',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description,
			short_description: '<p>This is a simple product.</p>\n',
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
			dimensions: { length: '10', width: '6', height: '3' },
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
			categories: [ { id: categories.hoodies.id } ],
			tags: [],
			attributes: [ 
				{ 
					id: attributes.color.id,
					position: 0,
					visible: true,
					variation: false,
					options: [ 'Blue' ] 
				} 
			],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [ 57, 65 ],
			stock_status: 'instock' 
		} 
	] );

	return simpleProducts.create;
};

const createSampleExternalProducts = async ( categories ) => {
	const { body: externalProducts } = await createProducts( [
		{
			name: 'WordPress Pennant',
			date_created_gmt: '2021-09-16T15:50:20',
			type: 'external',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description:
				'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. '
				+ 'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. '
				+ 'Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n',
			short_description: '<p>This is an external product.</p>\n',
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
			external_url: 'https://mercantile.wordpress.org/product/wordpress-pennant/',
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
			dimensions: { length: '', width: '', height: '' },
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
			categories: [ { id: categories.decor.id } ],
			tags: [],
			attributes: [],
			default_attributes: [],
			variations: [],
			grouped_products: [],
			menu_order: 0,
			related_ids: [],
			stock_status: 'instock'
		},
	] );

	return externalProducts.create;
};

const createSampleGroupedProduct = async ( categories ) => {
	const { body: logoProducts } = await getProducts( {
		search: 'logo',
		_fields: [ 'id' ],
	} );

	const { body: groupedProducts } = await createProducts( [
		{
			name: 'Logo Collection',
			date_created_gmt: '2021-09-17T15:50:20',
			type: 'grouped',
			status: 'publish',
			featured: false,
			catalog_visibility: 'visible',
			description:
				'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. '
				+ 'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. '
				+ 'Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n',
			short_description: '<p>This is a grouped product.</p>\n',
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
			dimensions: { length: '', width: '', height: '' },
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
			categories: [ { id: categories.clothing.id } ],
			tags: [],
			attributes: [],
			default_attributes: [],
			variations: [],
			grouped_products: logoProducts.map( p => p.id ),
			menu_order: 0,
			related_ids: [],
			stock_status: 'instock'
		},
	] );

	return groupedProducts.create;
};

const createSampleVariableProducts = async ( categories, attributes ) => {
	const description =	'<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. '
		+ 'Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. '
		+ 'Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n';
	const { body: hoodie } = await createProduct( {
		name: 'Hoodie',
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
		dimensions: { length: '10', width: '8', height: '3' },
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
		categories: [ { id: categories.hoodies.id } ],
		tags: [],
		attributes: [
			{
				id: attributes.color.id,
				position: 0,
				visible: true,
				variation: true,
				options: [ 'Blue', 'Green', 'Red' ]
			},
			{
				id: 0,
				name: 'Logo',
				position: 1,
				visible: true,
				variation: true,
				options: [ 'Yes', 'No' ]
			}
		],
		default_attributes: [],
		grouped_products: [],
		menu_order: 0,
		stock_status: 'instock'
	} );

	const variationDescription =
		'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. '
		+ 'Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. '
		+ 'Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. '
		+ 'Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. '
		+ 'Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. '
		+ 'Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.</p>\n';

	const { body: hoodieVariations } = await createProductVariations( hoodie.id, [
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
			dimensions: { length: '10', width: '8', height: '3' },
			shipping_class: '',
			attributes: [
				{ id: attributes.color.id, option: 'Blue' },
				{ id: 0, name: 'Logo', option: 'Yes' }
			],
			menu_order: 0
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
			dimensions: { length: '10', width: '8', height: '3' },
			shipping_class: '',
			attributes: [
				{ id: attributes.color.id, option: 'Blue' },
				{ id: 0, name: 'Logo', option: 'No' }
			],
			menu_order: 3
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
			dimensions: { length: '10', width: '8', height: '3' },
			shipping_class: '',
			attributes: [
				{ id: attributes.color.id, option: 'Green' },
				{ id: 0, name: 'Logo', option: 'No' }
			],
			menu_order: 2
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
			dimensions: { length: '10', width: '8', height: '3' },
			shipping_class: '',
			attributes: [
				{ id: attributes.color.id, option: 'Red' },
				{ id: 0, name: 'Logo', option: 'No' }
			],
			menu_order: 1
		}
	] );

	const { body: vneck } = await createProduct( {
		name: 'V-Neck T-Shirt',
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
		dimensions: { length: '24', width: '1', height: '2' },
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
		categories: [ { id: categories.tshirts.id } ],
		tags: [],
		attributes: [
			{
				id: attributes.color.id,
				position: 0,
				visible: true,
				variation: true,
				options: [ 'Blue', 'Green', 'Red' ]
			},
			{
				id: attributes.size.id,
				position: 1,
				visible: true,
				variation: true,
				options: [ 'Large', 'Medium', 'Small' ]
			}
		],
		default_attributes: [],
		grouped_products: [],
		menu_order: 0,
		stock_status: 'instock'
	} );

	const { body: vneckVariations } = await createProductVariations( vneck.id, [
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
			dimensions: { length: '24', width: '1', height: '2' },
			shipping_class: '',
			attributes: [ { id: attributes.color.id, option: 'Blue' } ],
			menu_order: 0
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
			dimensions: { length: '24', width: '1', height: '2' },
			shipping_class: '',
			attributes: [ { id: attributes.color.id, option: 'Green' } ],
			menu_order: 0
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
			dimensions: { length: '24', width: '1', height: '2' },
			shipping_class: '',
			attributes: [ { id: attributes.color.id, option: 'Red' } ],
			menu_order: 0
		}
	] );

	return {
		hoodie,
		hoodieVariations: hoodieVariations.create,
		vneck,
		vneckVariations: vneckVariations.create,
	};
};

const createSampleHierarchicalProducts = async () => {
	const { body: parent } = await createProduct( {
		name: 'Parent Product',
		date_created_gmt: '2021-09-27T15:50:19',
	} );

	const { body: child } = await createProduct( {
		name: 'Child Product',
		parent_id: parent.id,
		date_created_gmt: '2021-09-28T15:50:19',
	} );

	return {
		parent,
		child,
	}
};

const createSampleProductReviews = async ( simpleProducts ) => {
	const cap = simpleProducts.find( p => p.name === 'Cap' );
	const shirt = simpleProducts.find( p => p.name === 'T-Shirt' );
	const sunglasses = simpleProducts.find( p => p.name === 'Sunglasses' );

	let { body: review1 } = await createProductReview( cap.id, {
		rating: 3,
		review: 'Decent cap.',
		reviewer: 'John Doe',
		reviewer_email: 'john.doe@example.com',
	} );
	// We need to update the review in order for the product's
	// average_rating to be recalculated.
	// See: https://github.com/woocommerce/woocommerce/issues/29906.
	await updateProductReview( review1.id );

	let { body: review2 } = await createProductReview( shirt.id, {
		rating: 5,
		review: 'The BEST shirt ever!!',
		reviewer: 'Shannon Smith',
		reviewer_email: 'shannon.smith@example.com',
	} );
	await updateProductReview( review2.id );

	let { body: review3 } = await createProductReview( sunglasses.id, {
		rating: 1,
		review: 'These are way too expensive.',
		reviewer: 'Tim Frugalman',
		reviewer_email: 'timmyfrufru@example.com',
	} );
	await updateProductReview( review3.id );

	return [ review1.id, review2.id, review3.id ];
};

const createSampleProductOrders = async ( simpleProducts ) => {
	const single = simpleProducts.find( p => p.name === 'Single' );
	const beanie = simpleProducts.find( p => p.name === 'Beanie with Logo' );
	const shirt = simpleProducts.find( p => p.name === 'T-Shirt' );

	const { body: order } = await postRequest( 'orders', {
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
	} );

	return [ order ];
};

const createSampleData = async () => {
	const categories = await createSampleCategories();
	const attributes = await createSampleAttributes();
	const tags = await createSampleTags();
	const shippingClasses = await createSampleShippingClasses();
	const taxClasses = await createSampleTaxClasses();

	const simpleProducts = await createSampleSimpleProducts( categories, attributes, tags );
	const externalProducts = await createSampleExternalProducts( categories );
	const groupedProducts = await createSampleGroupedProduct( categories );
	const variableProducts = await createSampleVariableProducts( categories, attributes );
	const hierarchicalProducts = await createSampleHierarchicalProducts();

	const reviewIds = await createSampleProductReviews( simpleProducts );
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

const deleteSampleData = async ( sampleData ) => {
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
	} = sampleData;

	const productIds = [].concat(
		simpleProducts.map( p => p.id )
	).concat(
		externalProducts.map( p => p.id )
	).concat(
		groupedProducts.map( p => p.id )
	).concat( [
		variableProducts.hoodie.id,
		variableProducts.vneck.id,
	] ).concat( [
		hierarchicalProducts.parent.id,
		hierarchicalProducts.child.id,
	] );

	orders.forEach( async ( { id } ) => {
		await deleteRequest( `orders/${ id }`, true );
	} );

	productIds.forEach( async ( id ) => {
		await deleteRequest( `products/${ id }`, true );
	} );

	await deleteRequest( `products/attributes/${ attributes.color.id }`, true );
	await deleteRequest( `products/attributes/${ attributes.size.id }`, true );

	Object.values( categories ).forEach( async ( { id } ) => {
		await deleteRequest( `products/categories/${ id }`, true );
	} );

	Object.values( tags ).forEach( async ( { id } ) => {
		await deleteRequest( `products/tags/${ id }`, true );
	} );

	Object.values( shippingClasses ).forEach( async ( { id } ) => {
		await deleteRequest( `products/shipping_classes/${ id }`, true );
	} );

	Object.values( taxClasses ).forEach( async ( { slug } ) => {
		await deleteRequest( `taxes/classes/${ slug }`, true );
	} );
};

module.exports = {
	createSampleData,
	deleteSampleData,
};
