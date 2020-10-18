import { mock, MockProxy } from 'jest-mock-extended';
import { HTTPClient, HTTPResponse } from '../../../../http';
import { settingGroupRESTRepository } from '../setting-group';

describe( 'settingGroupRESTRepository', () => {
	let httpClient: MockProxy< HTTPClient >;
	let repository: ReturnType< typeof settingGroupRESTRepository >;

	beforeEach( () => {
		httpClient = mock< HTTPClient >();
		repository = settingGroupRESTRepository( httpClient );
	} );

	it( 'should list', async () => {
		httpClient.get.mockResolvedValue( new HTTPResponse(
			200,
			{},
			[
				{
					id: 'group_1',
					label: 'Test Group 1',
				},
				{
					id: 'group_2',
					label: 'Test Group 2',
				},
			],
		) );

		const list = await repository.list();

		expect( list ).toHaveLength( 2 );
		expect( list[ 0 ] ).toMatchObject( { id: 'group_1', label: 'Test Group 1' } );
		expect( list[ 1 ] ).toMatchObject( { id: 'group_2', label: 'Test Group 2' } );
		expect( httpClient.get ).toHaveBeenCalledWith( '/wc/v3/settings' );
	} );
} );
