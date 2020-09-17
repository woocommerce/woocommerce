import { Model } from '../../models/model';
import { AsyncFactory } from '../async-factory';

class DummyModel extends Model {
	public name: string = '';

	public constructor( partial?: Partial< DummyModel > ) {
		super();
		Object.assign( this, partial );
	}
}

describe( 'AsyncFactory', () => {
	let factory: AsyncFactory< DummyModel >;

	beforeEach( () => {
		let sequence = 1;

		factory = new AsyncFactory< DummyModel >(
			( { params } ) => {
				const model = new DummyModel();
				model.name = params.name ?? '';
				return model;
			},
			( model ) => {
				return Promise.resolve( new DummyModel( { id: sequence++, name: model.name } ) );
			},
		);
	} );

	it( 'should create', async () => {
		const model = await factory.create( { name: 'test-name' } );

		expect( model ).toHaveProperty( 'id', 1 );
		expect( model ).toHaveProperty( 'name', 'test-name' );
	} );

	it( 'should create many', async () => {
		const models = await factory.createList( 2, { name: 'test-name' } );

		expect( models ).toHaveLength( 2 );
		expect( models[ 0 ] ).toHaveProperty( 'id', 1 );
		expect( models[ 0 ] ).toHaveProperty( 'name', 'test-name' );
		expect( models[ 1 ] ).toHaveProperty( 'id', 2 );
		expect( models[ 1 ] ).toHaveProperty( 'name', 'test-name' );
	} );
} );
