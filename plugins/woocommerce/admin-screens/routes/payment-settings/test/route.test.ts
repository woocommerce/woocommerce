/**
 * External dependencies
 */
import { dispatch, resolveSelect, select } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, getCoreEntities } from '../entities';
import { route } from '../route';

jest.mock( '@wordpress/core-data', () => ( { store: { name: 'core' } } ) );
jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn(),
	resolveSelect: jest.fn(),
	select: jest.fn(),
} ) );
jest.mock( '@wordpress/route', () => ( {
	notFound: jest.fn( () => new Error( 'Not found' ) ),
} ) );

const addEntities = jest.fn();
const getEntityRecord = jest.fn();
const getEntityConfig = jest.fn();
const resolveCore = resolveSelect as jest.Mock<
	{ getEntityRecord: typeof getEntityRecord },
	[]
>;

beforeEach( () => {
	jest.clearAllMocks();
	jest.mocked( dispatch ).mockReturnValue( {
		addEntities,
	} );
	jest.mocked( select ).mockReturnValue( { getEntityConfig } );
	getEntityConfig.mockImplementation( ( _kind: string, name: string ) =>
		getCoreEntities().find( ( entity ) => entity.name === name )
	);
	resolveCore.mockReturnValue( { getEntityRecord } );
	getEntityRecord.mockResolvedValue( { id: 'cheque' } );
} );

it( 'loads a directly linked page from its entity registered before navigation', async () => {
	await route.beforeLoad( { params: { page: 'cheque' } } );
	await route.loader( { params: { page: 'cheque' } } );

	expect( addEntities ).toHaveBeenCalledWith(
		expect.arrayContaining( [
			expect.objectContaining( {
				name: 'cheque',
				baseURL: '/wc/v3/payment_gateways',
				key: 'id',
			} ),
		] )
	);
	expect( getEntityConfig ).toHaveBeenCalledWith( ENTITY_KIND, 'cheque' );
	expect(
		addEntities.mock.invocationCallOrder[ 0 ] ?? Infinity
	).toBeLessThan( getEntityConfig.mock.invocationCallOrder[ 0 ] ?? 0 );
	expect( getEntityRecord ).toHaveBeenCalledWith(
		'woo_settings_payments',
		'cheque',
		'cheque'
	);
} );

it.each( [ 'stripe', 'cheque/advanced', '__proto__' ] )(
	'does not expose the unsupported settings page %s',
	async ( page ) => {
		await expect(
			route.beforeLoad( { params: { page } } )
		).rejects.toThrow( 'Not found' );
		expect( notFound ).toHaveBeenCalled();
		expect( getEntityConfig ).toHaveBeenCalledWith( ENTITY_KIND, page );
		expect( getEntityRecord ).not.toHaveBeenCalled();
	}
);

it( 'rejects a page when its entity is not registered', async () => {
	getEntityConfig.mockReturnValueOnce( undefined );
	await expect(
		route.beforeLoad( { params: { page: 'cheque' } } )
	).rejects.toThrow( 'Not found' );
	expect( getEntityRecord ).not.toHaveBeenCalled();
} );

it( 'lets the stage display a failed settings request', async () => {
	getEntityRecord.mockRejectedValueOnce( { message: 'Permission denied' } );
	await expect(
		route.loader( { params: { page: 'cheque' } } )
	).resolves.toBeUndefined();
} );
