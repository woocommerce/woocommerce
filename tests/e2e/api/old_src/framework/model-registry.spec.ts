import { AdapterTypes, ModelRegistry } from './model-registry';
import { ModelFactory } from './model-factory';
import { Product } from '../models/product';
import { APIAdapter } from './api/api-adapter';
import { SimpleProduct } from '../models/simple-product';

describe( 'ModelRegistry', () => {
	let factoryRegistry: ModelRegistry;

	beforeEach( () => {
		factoryRegistry = new ModelRegistry();
	} );

	it( 'should register factories once', () => {
		const factory = ModelFactory.define< Product, any, ModelFactory< Product >>( ( { params } ) => {
			return new SimpleProduct( params );
		} );

		expect( factoryRegistry.getFactory( SimpleProduct ) ).toBeNull();

		factoryRegistry.registerFactory( SimpleProduct, factory );

		expect( () => factoryRegistry.registerFactory( SimpleProduct, factory ) )
			.toThrowError( /already been registered/ );

		const loaded = factoryRegistry.getFactory( SimpleProduct );

		expect( loaded ).toBe( factory );
	} );

	it( 'should register adapters once', () => {
		const adapter = new APIAdapter< Product >( '', ( model ) => model );

		expect( factoryRegistry.getAdapter( SimpleProduct, AdapterTypes.API ) ).toBeNull();

		factoryRegistry.registerAdapter( SimpleProduct, AdapterTypes.API, adapter );

		expect( () => factoryRegistry.registerAdapter( SimpleProduct, AdapterTypes.API, adapter ) )
			.toThrowError( /already been registered/ );

		const loaded = factoryRegistry.getAdapter( SimpleProduct, AdapterTypes.API );

		expect( loaded ).toBe( adapter );
	} );
} );
