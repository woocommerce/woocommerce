import { HTTPClient, HTTPResponse } from '../../http';
import { mock, MockProxy } from 'jest-mock-extended';
import { RESTRepository } from '../rest-repository';
import { RepositoryData } from '../repository';

class TestData implements RepositoryData {
	public constructor( public name: string ) {}
	onCreated( data: any ): void {
		this.name = data.name;
	}
}

describe( 'RESTRepository', () => {
	let httpClient: MockProxy< HTTPClient >;
	let repository: RESTRepository< TestData >;

	beforeEach( () => {
		httpClient = mock< HTTPClient >();
		repository = new RESTRepository< TestData >(
			( data ) => {
				return { transformedName: data.name };
			},
			{ create: '/testing' },
		);
		repository.setHTTPClient( httpClient );
	} );

	it( 'should create', async () => {
		const model = new TestData( 'testing' );

		httpClient.post.mockReturnValueOnce(
			Promise.resolve(
				new HTTPResponse( 200, {}, { id: 1, name: 'created' } ),
			),
		);

		const created = await repository.create( model );

		expect( created.name ).toEqual( 'created' );
		expect( httpClient.post.mock.calls[ 0 ][ 0 ] ).toEqual( '/testing' );
		expect( httpClient.post.mock.calls[ 0 ][ 1 ] ).toEqual( { transformedName: 'testing' } );
	} );
} );
