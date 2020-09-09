import { Model } from '../../models/model';
import { ModelTransformer } from '../model-transformer';

class TestData extends Model {
	public name: string = '';

	public onCreated( data: any ): void {
		super.onCreated( data );
		this.name = data.name;
	}
}

describe( 'ModelTransformer', () => {
	let transformer: ModelTransformer< TestData >;

	beforeEach( () => {
		transformer = new ModelTransformer<TestData>(
			( model ) => {
				return { id: model.id, name: model.name };
			},
			( data ) => {
				const model = new TestData();
				model.name = data.name;
				return model;
			},
		);
	} );

	it( 'should transform to server', () => {
		const model = new TestData();
		model.onCreated( { id: 1, name: 'Testing' } );

		const transformed = transformer.toServer( model );

		expect( transformed ).toEqual( { id: 1, name: 'Testing' } );
	} );

	it( 'should transform from server', () => {
		const transformed = transformer.fromServer( { name: 'Testing' } );

		expect( transformed ).toBeInstanceOf( TestData );
		expect( transformed.name ).toBe( 'Testing' );
	} );
} );
