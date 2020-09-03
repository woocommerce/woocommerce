import { ModelFactory } from './model-factory';
import { Adapter } from './adapter';
import { Product } from '../models/product';
import { SimpleProduct } from '../models/simple-product';

class MockAdapter implements Adapter<Product> {
	public create = jest.fn();
}

describe( 'ModelFactory', () => {
	let mockAdapter: MockAdapter;
	let factory: ModelFactory<Product>;

	beforeEach( () => {
		mockAdapter = new MockAdapter();
		factory = ModelFactory.define<Product, any, ModelFactory<Product>>(
			( { params } ) => {
				return new SimpleProduct( params );
			},
		);
	} );

	it( 'should error without adapter', async () => {
		expect( () => factory.create() ).toThrowError( /no adapter/ );
	} );

	it( 'should create using adapter', async () => {
		factory.setAdapter( mockAdapter );

		const expectedModel = new SimpleProduct( { name: 'test2' } );
		expectedModel.setID( 1 );
		mockAdapter.create.mockReturnValueOnce( Promise.resolve( expectedModel ) );

		const created = await factory.create( { name: 'test' } );

		expect( mockAdapter.create.mock.calls ).toHaveLength( 1 );
		expect( created ).toBeInstanceOf( Product );
		expect( created.id ).toBe( 1 );
		expect( created.name ).toBe( 'test2' );
	} );
} );
