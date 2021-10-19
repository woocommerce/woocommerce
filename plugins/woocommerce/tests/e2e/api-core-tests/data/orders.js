/**
 * Internal dependencies
 */
const { postRequest, deleteRequest } = require('../utils/request');
const productsTestSetup = require( './products' );
const { ordersApi } = require( '../endpoints/orders' );

const createCustomer = ( data ) => postRequest( 'customers', data );
const deleteCustomer = ( id ) => deleteRequest( `customers/${ id }`, true );

const createSampleData = async () => {
	const testProductData = await productsTestSetup.createSampleData();

	const orderedProducts = {
		pocketHoodie: testProductData.simpleProducts.find( p => p.name === 'Hoodie with Pocket' ),
		sunglasses: testProductData.simpleProducts.find( p => p.name === 'Sunglasses' ),
		beanie: testProductData.simpleProducts.find( p => p.name === 'Beanie' ),
		blueVneck: testProductData.variableProducts.vneckVariations.find( p => p.sku === 'woo-vneck-tee-blue' ),
		pennant: testProductData.externalProducts[0],
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
	const guestAddress = {
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

	const { body: john } = await createCustomer( {
		first_name: 'John',
		last_name: 'Doe',
		username: 'john.doe',
		email: 'john.doe@example.com',
		billing: {
			...johnAddress,
			email: 'john.doe@example.com',
		},
		shipping: johnAddress,
	} );

	const { body: tina } = await createCustomer( {
		first_name: 'Tina',
		last_name: 'Clark',
		username: 'tina.clark',
		email: 'tina.clark@example.com',
		billing: {
			...tinaAddress,
			email: 'tina.clark@example.com',
		},
		shipping: tinaAddress,
	} );

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
		const { body: order } = await ordersApi.create.order( {
			...orderBaseData,
			customer_id: john.id,
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
		} );

		orders.push( order );
	} );

	// Have "Tina" order some sunglasses and make a child order.
	// This somewhat resembles a subscription renewal, but we're just testing the `parent` field.
	const { body: order2 } = await ordersApi.create.order( {
		...orderBaseData,
		status: 'completed',
		set_paid: true,
		customer_id: tina.id,
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
	} );
	orders.push( order2 );

	const { body: order3 } = await ordersApi.create.order( {
		...orderBaseData,
		parent_id: order2.id,
		customer_id: tina.id,
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
	} );
	orders.push( order3 );

	// Guest order.
	const { body: guestOrder } = await ordersApi.create.order( {
		...orderBaseData,
		billing: {
			...guestAddress,
			email: 'ano.nymous@example.com',
		},
		shipping: guestAddress,
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
	} );

	return {
		customers: { john, tina },
		orders,
		guestOrder,
		testProductData,
	}
};

const deleteSampleData = async ( sampleData ) => {
	await productsTestSetup.deleteSampleData( sampleData.testProductData );

	sampleData.orders.concat( [ sampleData.guestOrder ] ).forEach( async ( { id } ) => {
		await ordersApi.delete.order( id, true );
	} );

	Object.values( sampleData.customers ).forEach( async ( { id } ) => {
		await deleteCustomer( id );
	} );
};

module.exports = {
	createSampleData,
	deleteSampleData,
};
