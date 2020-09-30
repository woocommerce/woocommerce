import { Model } from '../../models/model';
import { ModelRepository } from '../model-repository';

class DummyModel extends Model {
	public name: string = '';

	public constructor( partial?: Partial< DummyModel > ) {
		super();
		Object.assign( this, partial );
	}
}

describe( 'ModelRepository', () => {
	it( 'should list', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( [ model ] );
		const repository = new ModelRepository< DummyModel, { search: string } >( callback, null, null, null, null );

		const listed = await repository.list( { search: 'test' } );
		expect( listed ).toContain( model );
		expect( callback ).toHaveBeenCalledWith( { search: 'test' } );
	} );

	it( 'should throw error on list without callback', () => {
		const repository = new ModelRepository< DummyModel >( null, null, null, null, null );

		expect( () => repository.list() ).toThrowError( /not supported/i );
	} );

	it( 'should create', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository = new ModelRepository< DummyModel >( null, callback, null, null, null );

		const created = await repository.create( { name: 'test' } );
		expect( created ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( { name: 'test' } );
	} );

	it( 'should throw error on create without callback', () => {
		const repository = new ModelRepository< DummyModel >( null, null, null, null, null );

		expect( () => repository.create( { name: 'test' } ) ).toThrowError( /not supported/i );
	} );

	it( 'should read', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository = new ModelRepository< DummyModel >( null, null, callback, null, null );

		const created = await repository.read( 1 );
		expect( created ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( 1 );
	} );

	it( 'should throw error on read without callback', () => {
		const repository = new ModelRepository< DummyModel >( null, null, null, null, null );

		expect( () => repository.read( 1 ) ).toThrowError( /not supported/i );
	} );

	it( 'should update', async () => {
		const model = new DummyModel();
		const callback = jest.fn().mockResolvedValue( model );
		const repository = new ModelRepository< DummyModel, void, 'name' >( null, null, null, callback, null );

		const updated = await repository.update( 1, { name: 'new-name' } );
		expect( updated ).toBe( model );
		expect( callback ).toHaveBeenCalledWith( 1, { name: 'new-name' } );
	} );

	it( 'should throw error on update without callback', () => {
		const repository = new ModelRepository< DummyModel, void, 'name' >( null, null, null, null, null );

		expect( () => repository.update( 1, { name: 'new-name' } ) ).toThrowError( /not supported/i );
	} );

	it( 'should delete', async () => {
		const callback = jest.fn().mockResolvedValue( true );
		const repository = new ModelRepository< DummyModel >( null, null, null, null, callback );

		const success = await repository.delete( 1 );
		expect( success ).toBe( true );
		expect( callback ).toHaveBeenCalledWith( 1 );
	} );

	it( 'should throw error on delete without callback', () => {
		const repository = new ModelRepository< DummyModel >( null, null, null, null, null );

		expect( () => repository.delete( 1 ) ).toThrowError( /not supported/i );
	} );
} );
