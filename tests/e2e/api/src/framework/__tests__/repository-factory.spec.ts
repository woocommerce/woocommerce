import { mock, MockProxy } from 'jest-mock-extended';
import { Repository, RepositoryData } from '../repository';
import { RepositoryFactory } from '../repository-factory';

class TestData implements RepositoryData {
	public constructor( public name: string ) {}
	onCreated( data: any ): void {
		this.name = data.name;
	}
}

describe( 'RepositoryFactory', () => {
	let repository: MockProxy< Repository< TestData > >;

	beforeEach( () => {
		repository = mock< Repository< TestData > >();
	} );

	it( 'should error for create without repository', () => {
		const factory = new RepositoryFactory( () => new TestData( '' ) );

		expect( () => factory.create() ).toThrowError();
	} );

	it( 'should create using repository', async () => {
		const factory = new RepositoryFactory( () => new TestData( '' ) );
		factory.setRepository( repository );

		repository.create.mockReturnValueOnce( Promise.resolve( new TestData( 'created' ) ) );

		const created = await factory.create( { name: 'test' } );

		expect( created ).toBeInstanceOf( TestData );
		expect( created.name ).toEqual( 'created' );
	} );
} );
