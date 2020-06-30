import { Model } from '../models/model';
import { APIResponse, APIService } from '..';
import { APIAdapter } from './api-adapter';

class MockAPI implements APIService {
	public get = jest.fn();
	public post = jest.fn();
	public put = jest.fn();
	public patch = jest.fn();
	public delete = jest.fn();
}

class MockModel extends Model {}

describe( 'APIModelCreator', () => {
	let adapter: APIAdapter<Model>;
	let mockService: MockAPI;

	beforeEach( () => {
		adapter = new APIAdapter( '/wc/v3/product', () => 'test' );
		mockService = new MockAPI();
		adapter.setAPIService( mockService );
	} );

	it( 'should create single instance', async () => {
		mockService.post.mockReturnValueOnce( new APIResponse( 200, {}, { id: 1 } ) );

		const result = await adapter.create( new MockModel() );

		expect( result ).toBeInstanceOf( MockModel );
		expect( result.ID ).toBe( 1 );
		expect( mockService.post.mock.calls[ 0 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 0 ][ 1 ] ).toBe( 'test' );
	} );

	it( 'should create multiple instances', async () => {
		mockService.post.mockReturnValueOnce( new APIResponse( 200, {}, { id: 1 } ) )
			.mockReturnValueOnce( new APIResponse( 200, {}, { id: 2 } ) )
			.mockReturnValueOnce( new APIResponse( 200, {}, { id: 3 } ) );

		const result = await adapter.create( [ new MockModel(), new MockModel(), new MockModel() ] );

		expect( result ).toBeInstanceOf( Array );
		expect( result ).toHaveLength( 3 );
		expect( result[ 0 ].ID ).toBe( 1 );
		expect( result[ 1 ].ID ).toBe( 2 );
		expect( result[ 2 ].ID ).toBe( 3 );
		expect( mockService.post.mock.calls[ 0 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 0 ][ 1 ] ).toBe( 'test' );
		expect( mockService.post.mock.calls[ 1 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 1 ][ 1 ] ).toBe( 'test' );
		expect( mockService.post.mock.calls[ 2 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 2 ][ 1 ] ).toBe( 'test' );
	} );
} );
