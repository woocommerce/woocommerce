import { Model } from '../model';
import { APIAdapter } from './api-adapter';
import { SimpleProduct } from '../../models/simple-product';
import { APIResponse, APIService } from './api-service';

class MockAPI implements APIService {
	public get = jest.fn();
	public post = jest.fn();
	public put = jest.fn();
	public patch = jest.fn();
	public delete = jest.fn();
}

describe( 'APIModelCreator', () => {
	let adapter: APIAdapter< Model >;
	let mockService: MockAPI;

	beforeEach( () => {
		adapter = new APIAdapter( '/wc/v3/product', () => 'test' );
		mockService = new MockAPI();
		adapter.setAPIService( mockService );
	} );

	it( 'should create single instance', async () => {
		mockService.post.mockReturnValueOnce( Promise.resolve( new APIResponse( 200, {}, { id: 1 } ) ) );

		const result = await adapter.create( new SimpleProduct() );

		expect( result ).toBeInstanceOf( SimpleProduct );
		expect( result.id ).toBe( 1 );
		expect( mockService.post.mock.calls[ 0 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 0 ][ 1 ] ).toBe( 'test' );
	} );

	it( 'should create multiple instances', async () => {
		mockService.post
			.mockReturnValueOnce( Promise.resolve( new APIResponse( 200, {}, { id: 1 } ) ) )
			.mockReturnValueOnce( Promise.resolve( new APIResponse( 200, {}, { id: 2 } ) ) )
			.mockReturnValueOnce( Promise.resolve( new APIResponse( 200, {}, { id: 3 } ) ) );

		const result = await adapter.create( [ new SimpleProduct(), new SimpleProduct(), new SimpleProduct() ] );

		expect( result ).toBeInstanceOf( Array );
		expect( result ).toHaveLength( 3 );
		expect( result[ 0 ].id ).toBe( 1 );
		expect( result[ 1 ].id ).toBe( 2 );
		expect( result[ 2 ].id ).toBe( 3 );
		expect( mockService.post.mock.calls[ 0 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 0 ][ 1 ] ).toBe( 'test' );
		expect( mockService.post.mock.calls[ 1 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 1 ][ 1 ] ).toBe( 'test' );
		expect( mockService.post.mock.calls[ 2 ][ 0 ] ).toBe( '/wc/v3/product' );
		expect( mockService.post.mock.calls[ 2 ][ 1 ] ).toBe( 'test' );
	} );
} );
