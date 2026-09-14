/**
 * Internal dependencies
 */
import { getPluginActionErrorMessage, getFailedPluginAction } from '../utils';

describe( 'getPluginActionErrorMessage', () => {
	const reason =
		'The package could not be installed. The PHP version on your server is 8.1.34, however the uploaded plugin requires 8.2.0.';

	it( 'names the provider by title and appends the reason the rejection carries', () => {
		const message = getPluginActionErrorMessage(
			'install',
			'Visa Acceptance Solutions',
			{
				message:
					'Could not install visa-acceptance-solutions. ' + reason,
				reason,
			}
		);

		expect( message ).toBe(
			'Could not install Visa Acceptance Solutions. ' + reason
		);
	} );

	it( 'uses the activate frame for a failed activation', () => {
		expect(
			getPluginActionErrorMessage( 'activate', 'Foo', {
				message: 'x',
				reason: 'One. Two.',
			} )
		).toBe( 'Could not activate Foo. One. Two.' );
	} );

	it( 'falls back to the framed message when the rejection carries no reason', () => {
		const framed =
			'Could not install visa-acceptance-solutions. Something.';

		expect(
			getPluginActionErrorMessage(
				'install',
				'Visa Acceptance Solutions',
				{
					message: framed,
					reason: '',
				}
			)
		).toBe( framed );
		expect(
			getPluginActionErrorMessage(
				'install',
				'Visa Acceptance Solutions',
				new Error( framed )
			)
		).toBe( framed );
	} );

	it( 'falls back to the frame alone when the error carries no message', () => {
		expect(
			getPluginActionErrorMessage( 'install', 'Foo', undefined )
		).toBe( 'Could not install Foo.' );
	} );
} );

describe( 'getFailedPluginAction', () => {
	it( 'prefers the step the rejection reports over the requested one', () => {
		// Install succeeded, activation failed: the pre-request status still says
		// 'not_installed', so only the rejection knows the notice needs "activate".
		expect(
			getFailedPluginAction( { actionType: 'activate' }, 'install' )
		).toBe( 'activate' );
	} );

	it( 'falls back to the requested step when the rejection reports none', () => {
		expect(
			getFailedPluginAction( new Error( 'Failed to fetch' ), 'install' )
		).toBe( 'install' );
		expect( getFailedPluginAction( undefined, 'activate' ) ).toBe(
			'activate'
		);
	} );

	it( 'ignores an unrecognised reported step', () => {
		expect(
			getFailedPluginAction( { actionType: 'nope' }, 'activate' )
		).toBe( 'activate' );
	} );
} );
