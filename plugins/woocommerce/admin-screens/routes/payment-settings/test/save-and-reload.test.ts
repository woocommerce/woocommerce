/**
 * Internal dependencies
 */
import { saveAndReload } from '../save-and-reload';

describe( 'saveAndReload', () => {
	it( 'reloads after a successful save and reports success', async () => {
		const calls: string[] = [];

		const notice = await saveAndReload( {
			save: () => Promise.resolve( calls.push( 'save' ) ),
			reload: () => Promise.resolve( calls.push( 'reload' ) ),
		} );

		expect( calls ).toEqual( [ 'save', 'reload' ] );
		expect( notice ).toEqual( {
			status: 'success',
			message: 'Settings saved.',
		} );
	} );

	it( 'reloads after a failed save and reports the server message', async () => {
		const reload = jest.fn( () => Promise.resolve() );

		const notice = await saveAndReload( {
			save: () =>
				Promise.reject(
					new Error( 'The support email is not valid.' )
				),
			reload,
		} );

		expect( reload ).toHaveBeenCalledTimes( 1 );
		expect( notice ).toEqual( {
			status: 'error',
			message: 'The support email is not valid.',
		} );
	} );

	it( 'reports the message of a REST error object', async () => {
		const notice = await saveAndReload( {
			// core-data rethrows apiFetch's plain `{ code, message }` object.
			save: () =>
				// eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors -- The test needs a non-Error rejection.
				Promise.reject( {
					code: 'invalid_email',
					message: 'The support email is not valid.',
				} ),
			reload: () => Promise.resolve(),
		} );

		expect( notice ).toEqual( {
			status: 'error',
			message: 'The support email is not valid.',
		} );
	} );

	it( 'still reports the save result when the reload fails', async () => {
		const notice = await saveAndReload( {
			save: () => Promise.resolve(),
			reload: () => Promise.reject( new Error( 'Offline' ) ),
		} );

		expect( notice.status ).toBe( 'success' );
	} );
} );
