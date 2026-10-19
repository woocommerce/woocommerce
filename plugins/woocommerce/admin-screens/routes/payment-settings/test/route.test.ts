/**
 * External dependencies
 */
import { dispatch, resolveSelect } from '@wordpress/data';
import { notFound } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { route } from '../route';

jest.mock( '@wordpress/core-data', () => ( { store: { name: 'core' } } ) );
jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn(),
	resolveSelect: jest.fn(),
} ) );
jest.mock( '@wordpress/route', () => ( {
	notFound: jest.fn( () => new Error( 'Not found' ) ),
} ) );

const addEntities = jest.fn();
const getEntityRecord = jest.fn();
const resolveCore = resolveSelect as jest.Mock<
	{ getEntityRecord: typeof getEntityRecord },
	[]
>;

beforeEach( () => {
	jest.clearAllMocks();
	jest.mocked( dispatch ).mockReturnValue( {
		addEntities,
	} );
	resolveCore.mockReturnValue( { getEntityRecord } );
	getEntityRecord.mockResolvedValue( { id: 'cod' } );
} );

it( 'loads a directly linked Core gateway from the v4 entity registered before navigation', async () => {
	await route.beforeLoad( { params: { page: 'cod' } } );
	await route.loader( { params: { page: 'cod' } } );

	expect( addEntities ).toHaveBeenCalledWith(
		expect.arrayContaining( [
			expect.objectContaining( {
				name: 'cod',
				baseURL: '/wc/v4/settings/payment-gateways',
				key: 'id',
			} ),
		] )
	);
	expect( getEntityRecord ).toHaveBeenCalledWith(
		'woo_settings_payments',
		'cod',
		'cod'
	);
} );

it.each( [ 'stripe', 'bacs/advanced', '__proto__' ] )(
	'does not expose the unsupported settings page %s',
	async ( page ) => {
		await expect(
			route.beforeLoad( { params: { page } } )
		).rejects.toThrow( 'Not found' );
		expect( notFound ).toHaveBeenCalled();
		expect( addEntities ).not.toHaveBeenCalled();
		expect( getEntityRecord ).not.toHaveBeenCalled();
	}
);

it( 'lets the stage display a failed settings request', async () => {
	getEntityRecord.mockRejectedValueOnce( { message: 'Permission denied' } );
	await expect(
		route.loader( { params: { page: 'cheque' } } )
	).resolves.toBeUndefined();
} );
