import { simpleProductRESTRepository } from '../simple-product';
import { mock, MockProxy } from 'jest-mock-extended';
import { HTTPClient, HTTPResponse } from '../../../../http';
import { SimpleProduct } from '../../../../models';
import { CreatesModels } from '../../../../framework/model-repository';

describe( 'simpleProductRESTRepository', () => {
	let httpClient: MockProxy< HTTPClient >;
	let repository: CreatesModels< SimpleProduct >;

	beforeEach( () => {
		httpClient = mock< HTTPClient >();
		repository = simpleProductRESTRepository( httpClient );
	} );

	it( 'should create', async () => {
		httpClient.post.mockResolvedValue( new HTTPResponse(
			200,
			{},
			{ id: 123 },
		) );

		const created = await repository.create( { name: 'Test Product' } );

		expect( created ).toBeInstanceOf( SimpleProduct );
		expect( created ).toMatchObject( { id: 123 } );
		expect( httpClient.post ).toHaveBeenCalledWith( '/wc/v3/products', { type: 'simple', name: 'Test Product' } );
	} );
} );
