/**
 * Internal dependencies
 */
 const { productsApi } = require('../../endpoints/products');

 /**
  * Tests for the WooCommerce Products API.
  * These tests cover API endpoints for creating, retrieving, updating, and deleting a single product.
  *
  * @group api
  * @group products
  *
  */
  describe( 'Products CRUD API tests', () => {
 
	 let productId;
 
	 it( 'can add a simple product', async () => {
		 const product = {
			 name: 'A Simple Product',
			 regular_price: '25',
			 description: 'Description for this simple product.',
			 short_description: 'Shorter description.',
		 };
		 const { status, body } = await productsApi.create.product( product );
		 productId = body.id;
 
		 expect( status ).toEqual( productsApi.create.responseCode );
		 expect( productId ).toBeDefined();
		 expect( body ).toMatchObject( product );
		 expect( body.type ).toEqual( 'simple' );
		 expect( body.status ).toEqual( 'publish' );
		 expect( body.virtual ).toEqual( false );
		 expect( body.downloadable ).toEqual( false );
		 expect( body.shipping_required ).toEqual( true );
	 } );
 
	 it( 'can add a virtual product', async () => {
		 const virtualProduct = {
			 name: 'A Virtual Product',
			 regular_price: '10',
			 virtual: true,
		 };
		 const { status, body } = await productsApi.create.product(
			 virtualProduct
		 );
		 const virtualProductId = body.id;
 
		 expect( status ).toEqual( productsApi.create.responseCode );
		 expect( virtualProductId ).toBeDefined();
		 expect( body ).toMatchObject( virtualProduct );
		 expect( body.type ).toEqual( 'simple' );
		 expect( body.status ).toEqual( 'publish' );
		 expect( body.shipping_required ).toEqual( false );
 
		 // Cleanup: Delete the virtual product
		 await productsApi.delete.product( virtualProductId, true );
	 } );
 
	 it( 'can add a variable product', async () => {
		 const variableProduct = {
			 name: 'A Variable Product',
			 type: 'variable',
			 attributes: [
				 {
					 name: 'Colour',
					 visible: true,
					 variation: true,
					 options: [ 'Red', 'Green', 'Blue' ],
				 },
				 {
					 name: 'Size',
					 visible: true,
					 variation: true,
					 options: [ 'Small', 'Medium', 'Large' ],
				 },
				 {
					 name: 'Logo',
					 visible: true,
					 variation: true,
					 options: [ 'Woo', 'WordPress' ],
				 },
			 ],
		 };
		 const { status, body } = await productsApi.create.product(
			 variableProduct
		 );
		 const variableProductId = body.id;
 
		 expect( status ).toEqual( productsApi.create.responseCode );
		 expect( variableProductId ).toBeDefined();
		 expect( body ).toMatchObject( variableProduct );
		 expect( body.status ).toEqual( 'publish' );
 
		 // Cleanup: Delete the variable product
		 await productsApi.delete.product( variableProductId, true );
	 } );
 
	 it( 'can view a single product', async () => {
		 const { status, body } = await productsApi.retrieve.product( productId );
 
		 expect( status ).toEqual( productsApi.retrieve.responseCode );
		 expect( body.id ).toEqual( productId );
	 } );
 
	 it( 'can update a single product', async () => {
		 const updatePayload = {
			 regular_price: '25.99',
		 };
		 const { status, body } = await productsApi.update.product(
			 productId,
			 updatePayload
		 );
 
		 expect( status ).toEqual( productsApi.update.responseCode );
		 expect( body.id ).toEqual( productId );
		 expect( body.regular_price ).toEqual( updatePayload.regular_price );
	 } );
 
	 it( 'can delete a product', async () => {
		 const { status, body } = await productsApi.delete.product( productId, true );
 
		 expect( status ).toEqual( productsApi.delete.responseCode );
		 expect( body.id ).toEqual( productId );
		 
		 // Verify that the product can no longer be retrieved.
		 const {
			 status: retrieveDeletedProductStatus,
		 } = await productsApi.retrieve.product( productId );
		 expect( retrieveDeletedProductStatus ).toEqual( 404 );
	 } );
 } );
 