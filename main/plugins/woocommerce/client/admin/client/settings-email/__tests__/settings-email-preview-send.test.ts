/**
 * Internal dependencies
 */
import {
	friendlyEmailSendError,
	WPError,
} from '../settings-email-preview-send';

const makeError = ( overrides: Partial< WPError > = {} ): WPError => ( {
	code: '',
	message: '',
	data: { status: 0 },
	...overrides,
} );

describe( 'friendlyEmailSendError', () => {
	it( 'maps the WP core nonce error code to the session-expired message', () => {
		const result = friendlyEmailSendError(
			makeError( { code: 'rest_cookie_invalid_nonce' } )
		);

		expect( result ).toBe(
			'Your session expired. Refresh the page and try again.'
		);
	} );

	it( 'maps the Woo invalid_nonce code to the session-expired message', () => {
		const result = friendlyEmailSendError(
			makeError( { code: 'invalid_nonce' } )
		);

		expect( result ).toBe(
			'Your session expired. Refresh the page and try again.'
		);
	} );

	it( 'maps rest_invalid_json to the unexpected-output message', () => {
		const result = friendlyEmailSendError(
			makeError( { code: 'rest_invalid_json' } )
		);

		expect( result ).toBe(
			'The server returned unexpected output. Check your error log, or disable recently added plugins.'
		);
	} );

	it( 'maps a WSOD "critical error" message to the PHP-error message', () => {
		const result = friendlyEmailSendError(
			makeError( {
				code: 'some_other_code',
				message: 'There has been a critical error on this website.',
			} )
		);

		expect( result ).toBe(
			'A PHP error stopped the send. Check your error log or contact your host.'
		);
	} );

	it( 'maps woocommerce_rest_email_preview_not_rendered to the render-failed message', () => {
		const result = friendlyEmailSendError(
			makeError( {
				code: 'woocommerce_rest_email_preview_not_rendered',
			} )
		);

		expect( result ).toBe(
			"The email couldn't be rendered. Try resetting the template in Settings → Emails."
		);
	} );

	it( 'maps the no-valid-response message to the timeout message', () => {
		const result = friendlyEmailSendError(
			makeError( {
				message: 'Could not get a valid response from the server.',
			} )
		);

		expect( result ).toBe(
			'Your server timed out. If it keeps happening, ask your host to check PHP execution limits.'
		);
	} );

	it( 'falls back to the generic message for unknown errors', () => {
		const result = friendlyEmailSendError(
			makeError( {
				code: 'something_unexpected',
				message: 'Something random went wrong.',
			} )
		);

		expect( result ).toBe(
			"Couldn't send the test email. Check your email settings and try again."
		);
	} );

	it( 'still resolves by code when the message is localized (regression: locale fragility)', () => {
		// Simulates a translated site where the backend error message has
		// been run through __(). The mapping must still work because we
		// match on the stable code, not the English message.
		const result = friendlyEmailSendError(
			makeError( {
				code: 'woocommerce_rest_email_preview_not_rendered',
				message:
					"Une erreur s'est produite lors du rendu de l'aperçu de l'e-mail.",
			} )
		);

		expect( result ).toBe(
			"The email couldn't be rendered. Try resetting the template in Settings → Emails."
		);
	} );
} );
