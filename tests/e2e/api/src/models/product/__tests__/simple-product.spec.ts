import { SimpleProduct } from '../simple-product';
import { mock, MockProxy } from 'jest-mock-extended';
import { HTTPClient, HTTPResponse } from '../../../http';
import { ModelRepository } from '../../../framework/model-repository';

describe( 'SimpleProduct', () => {
	describe( 'restRepository', () => {
		let httpClient: MockProxy< HTTPClient >;
		let repository: ModelRepository< SimpleProduct >;

		beforeEach( () => {
			httpClient = mock< HTTPClient >();
			repository = SimpleProduct.restRepository( httpClient );
		} );

		it( 'should create', async () => {
			httpClient.post.mockResolvedValue( new HTTPResponse( 200, {}, { id: 2 } ) );

			const created = await repository.create( new SimpleProduct( { name: 'test' } ) );

			expect( created ).toHaveProperty( 'id', 2 );
			expect( httpClient.post ).toHaveBeenCalledWith(
				'/wc/v3/products',
				{
					name: 'test',
				},
			);
		} );

		it( 'should read', async () => {
			httpClient.get.mockResolvedValue(
				new HTTPResponse( 200, {}, {
					id: 12,
					name: 'test-name',
				} ),
			);

			const read = await repository.read( { id: 12 } );

			expect( read ).toHaveProperty( 'id', 12 );
			expect( httpClient.get ).toHaveBeenCalledWith( '/wc/v3/products/12' );
		} );

		it( 'should update', async () => {
			httpClient.put.mockResolvedValue( new HTTPResponse( 200, {}, { id: 1 } ) );

			const updated = await repository.update( new SimpleProduct( { id: 1, name: 'test' } ) );

			expect( updated ).toHaveProperty( 'id', 1 );
			expect( httpClient.put ).toHaveBeenCalledWith(
				'/wc/v3/products/1',
				{
					id: 1,
					name: 'test',
				},
			);
		} );

		it( 'should delete', async () => {
			httpClient.delete.mockResolvedValue( new HTTPResponse( 200, {}, {} ) );

			const response = await repository.delete( new SimpleProduct( { id: 123 } ) );

			expect( response ).toBeTruthy();
			expect( httpClient.delete ).toHaveBeenCalledWith( '/wc/v3/products/123' );
		} );
	} );
} );
