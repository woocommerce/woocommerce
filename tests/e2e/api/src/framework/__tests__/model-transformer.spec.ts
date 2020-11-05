import { Model } from '../../models/model';
import { ModelTransformer } from '../model-transformer';

class DummyModel extends Model {
	public name: string = '';

	public constructor( partial?: Partial< DummyModel > ) {
		super();
		Object.assign( this, partial );
	}
}

describe( 'ModelTransformer', () => {
	describe( 'fromModel', () => {
		it( 'should convert models plainly', () => {
			const model = new DummyModel( { name: 'Test' } );

			const transformed = ModelTransformer.fromModel( model );

			expect( transformed ).toMatchObject(
				{
					name: 'Test',
				},
			);
		} );

		it( 'should convert models with key changes', () => {
			const model = new DummyModel( { name: 'Test' } );

			const transformed = ModelTransformer.fromModel(
				model,
				{ name: 'new-test' },
			);

			expect( transformed ).toMatchObject(
				{
					'new-test': 'Test',
				},
			);
		} );

		it( 'should convert models with transformations', () => {
			const model = new DummyModel( { name: 'Test' } );

			const transformed = ModelTransformer.fromModel(
				model,
				undefined,
				{ name: ( val ) => 'Transform-' + val },
			);

			expect( transformed ).toMatchObject(
				{
					name: 'Transform-Test',
				},
			);
		} );
	} );

	describe( 'toModel', () => {
		it( 'should create models plainly', () => {
			const transformed = ModelTransformer.toModel(
				DummyModel,
				{ name: 'Test' },
			);

			expect( transformed ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		} );

		it( 'should create models with key changes', () => {
			const transformed = ModelTransformer.toModel(
				DummyModel,
				{ test: 'Test' },
				{ test: 'name' },
			);

			expect( transformed ).toMatchObject( new DummyModel( { name: 'Test' } ) );
		} );

		it( 'should create models with transformations', () => {
			const transformed = ModelTransformer.toModel(
				DummyModel,
				{ name: 'Test' },
				undefined,
				{ name: ( val ) => 'Transform-' + val },
			);

			expect( transformed ).toMatchObject( new DummyModel( { name: 'Transform-Test' } ) );
		} );
	} );
} );
