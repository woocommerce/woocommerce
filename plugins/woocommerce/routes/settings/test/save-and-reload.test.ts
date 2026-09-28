/**
 * Internal dependencies
 */
import { saveAndReload } from '../save-and-reload';

describe( 'saveAndReload', () => {
	it( 'reloads after a successful save and reports success', async () => {
		const calls: string[] = [];

		const notice = await saveAndReload( {
			save: async () => calls.push( 'save' ),
			reload: async () => calls.push( 'reload' ),
		} );

		expect( calls ).toEqual( [ 'save', 'reload' ] );
		expect( notice ).toEqual( {
			status: 'success',
			message: 'Settings saved.',
		} );
	} );

	it( 'reloads after a failed save and reports the server message', async () => {
		const reload = jest.fn( async () => undefined );

		const notice = await saveAndReload( {
			save: async () => {
				throw new Error( 'The support email is not valid.' );
			},
			reload,
		} );

		expect( reload ).toHaveBeenCalledTimes( 1 );
		expect( notice ).toEqual( {
			status: 'error',
			message: 'The support email is not valid.',
		} );
	} );

	it( 'still reports the save result when the reload fails', async () => {
		const notice = await saveAndReload( {
			save: async () => undefined,
			reload: async () => {
				throw new Error( 'Offline' );
			},
		} );

		expect( notice.status ).toBe( 'success' );
	} );
} );
