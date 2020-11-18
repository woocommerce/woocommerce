import { ModelTransformation, ModelTransformer } from '../model-transformer';
import { DummyModel } from '../../__test_data__/dummy-model';

class DummyTransformation implements ModelTransformation {
	public readonly fromModelOrder: number;

	private readonly fn: ( ( p: any ) => any ) | null;

	public constructor( order: number, fn: ( ( p: any ) => any ) | null ) {
		this.fromModelOrder = order;
		this.fn = fn;
	}

	public fromModel( properties: any ): any {
		if ( ! this.fn ) {
			return properties;
		}

		return this.fn( properties );
	}

	public toModel( properties: any ): any {
		if ( ! this.fn ) {
			return properties;
		}

		return this.fn( properties );
	}
}

describe( 'ModelTransformer', () => {
	it( 'should order transformers correctly', () => {
		const fn1 = jest.fn();
		fn1.mockReturnValue( { name: 'fn1' } );
		const fn2 = jest.fn();
		fn2.mockReturnValue( { name: 'fn2' } );

		const transformer = new ModelTransformer< DummyModel >(
			[
				// Ensure the orders are backwards so sorting is tested.
				new DummyTransformation( 1, fn2 ),
				new DummyTransformation( 0, fn1 ),
			],
		);

		let transformed = transformer.fromModel( new DummyModel( { name: 'fn0' } ) );

		expect( fn1 ).toHaveBeenCalledWith( { name: 'fn0' } );
		expect( fn2 ).toHaveBeenCalledWith( { name: 'fn1' } );
		expect( transformed ).toMatchObject( { name: 'fn2' } );

		// Reset and make sure "toModel" happens in reverse order.
		fn1.mockClear();
		fn2.mockClear();

		transformed = transformer.toModel( DummyModel, { name: 'fn3' } );

		expect( fn2 ).toHaveBeenCalledWith( { name: 'fn3' } );
		expect( fn1 ).toHaveBeenCalledWith( { name: 'fn2' } );
		expect( transformed ).toMatchObject( { name: 'fn1' } );
	} );

	it( 'should transform to model', () => {
		const transformer = new ModelTransformer< DummyModel >(
			[
				new DummyTransformation(
					0,
					( p: any ) => {
						p.name = 'Transformed-' + p.name;
						return p;
					},
				),
			],
		);

		const model = transformer.toModel( DummyModel, { name: 'Test' } );

		expect( model ).toBeInstanceOf( DummyModel );
		expect( model.name ).toEqual( 'Transformed-Test' );
	} );

	it( 'should transform from model', () => {
		const transformer = new ModelTransformer< DummyModel >(
			[
				new DummyTransformation(
					0,
					( p: any ) => {
						p.name = 'Transformed-' + p.name;
						return p;
					},
				),
			],
		);

		const transformed = transformer.fromModel( new DummyModel( { name: 'Test' } ) );

		expect( transformed ).not.toBeInstanceOf( DummyModel );
		expect( transformed.name ).toEqual( 'Transformed-Test' );
	} );
} );
