/**
 * Internal dependencies
 */
import { getPluginActionErrorMessage, getFailedPluginAction } from '../utils';

describe( 'getPluginActionErrorMessage', () => {
	const reason =
		'The package could not be installed. The PHP version on your server is 8.1.34, however the uploaded plugin requires 8.2.0.';

	it( 'names the provider by title and appends the server reason', () => {
		const message = getPluginActionErrorMessage(
			'install',
			'Visa Acceptance Solutions',
			'visa-acceptance-solutions',
			{
				message:
					'Could not install visa-acceptance-solutions. ' + reason,
				data: { 'visa-acceptance-solutions': [ reason ] },
			}
		);

		expect( message ).toBe(
			'Could not install Visa Acceptance Solutions. ' + reason
		);
	} );

	it( 'joins several server reasons with a space', () => {
		const message = getPluginActionErrorMessage( 'activate', 'Foo', 'foo', {
			message: 'x',
			data: { foo: [ 'One.', 'Two.' ] },
		} );

		expect( message ).toBe( 'Could not activate Foo. One. Two.' );
	} );

	it( 'names the provider by title on a permission error', () => {
		expect(
			getPluginActionErrorMessage(
				'install',
				'Visa Acceptance Solutions',
				'visa-acceptance-solutions',
				{
					message:
						'Could not install visa-acceptance-solutions. You do not have permissions to manage plugins. Please contact your site administrator.',
					data: {
						code: 'woocommerce_rest_cannot_update',
						message: 'Sorry, you cannot manage plugins.',
						data: { status: 403 },
					},
				}
			)
		).toBe(
			'Could not install Visa Acceptance Solutions. You do not have permissions to manage plugins. Please contact your site administrator.'
		);
	} );

	it( 'names the provider by title and appends the underlying message on other errors', () => {
		expect(
			getPluginActionErrorMessage(
				'install',
				'Visa Acceptance Solutions',
				'visa-acceptance-solutions',
				{
					message:
						'Could not install visa-acceptance-solutions. Failed to fetch',
					data: new TypeError( 'Failed to fetch' ),
				}
			)
		).toBe(
			'Could not install Visa Acceptance Solutions. Failed to fetch'
		);
	} );

	it( 'falls back to the framed message when the rejection carries no usable data', () => {
		const framed =
			'Could not install visa-acceptance-solutions. Something.';

		expect(
			getPluginActionErrorMessage(
				'install',
				'Visa Acceptance Solutions',
				'visa-acceptance-solutions',
				{ message: framed, data: undefined }
			)
		).toBe( framed );
	} );

	it( 'falls back to a generic sentence when the error carries no message', () => {
		expect(
			getPluginActionErrorMessage( 'install', 'Foo', 'foo', undefined )
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
