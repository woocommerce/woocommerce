import { AdapterTypes, FactoryRegistry } from './factory-registry';
import { ModelFactory } from './model-factory';
import { Product } from '../models/product';
import { APIAdapter } from './api-adapter';

describe( 'FactoryRegistry', () => {
	let factoryRegistry: FactoryRegistry;

	beforeEach( () => {
		factoryRegistry = new FactoryRegistry();
	} );

	it( 'should register factories once', () => {
		const factory = ModelFactory.define<Product, any, ModelFactory<Product>>( ( { params } ) => {
			return new Product( params );
		} );

		expect( factoryRegistry.getFactory( Product ) ).toBeNull();

		factoryRegistry.registerFactory( Product, factory );

		expect( () => factoryRegistry.registerFactory( Product, factory ) ).toThrowError( /already been registered/ );

		const loaded = factoryRegistry.getFactory( Product );

		expect( loaded ).toBe( factory );
	} );

	it( 'should register adapters once', () => {
		const adapter = new APIAdapter<Product>( '', ( model ) => model );

		expect( factoryRegistry.getAdapter( Product, AdapterTypes.API ) ).toBeNull();

		factoryRegistry.registerAdapter( Product, AdapterTypes.API, adapter );

		expect( () => factoryRegistry.registerAdapter( Product, AdapterTypes.API, adapter ) ).toThrowError( /already been registered/ );

		const loaded = factoryRegistry.getAdapter( Product, AdapterTypes.API );

		expect( loaded ).toBe( adapter );
	} );
} );
