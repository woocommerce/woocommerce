/**
 * Internal dependencies
 */
import { getPluginActionErrorMessage } from '../utils';

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

	it( 'falls back to the framed message when the error is not a per-plugin response', () => {
		const framed =
			'Could not install visa-acceptance-solutions. You do not have permissions to manage plugins. Please contact your site administrator.';

		expect(
			getPluginActionErrorMessage(
				'install',
				'Visa Acceptance Solutions',
				'visa-acceptance-solutions',
				{
					message: framed,
					data: { code: 'woocommerce_rest_cannot_update' },
				}
			)
		).toBe( framed );
	} );

	it( 'falls back to a generic sentence when the error carries no message', () => {
		expect(
			getPluginActionErrorMessage( 'install', 'Foo', 'foo', undefined )
		).toBe( 'Could not install Foo.' );
	} );
} );
