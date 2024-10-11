/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SetupNotice, { setupErrorTypes } from '../';

describe( 'SetupNotice', () => {
	it( 'should be hidden for no error', () => {
		const { container } = render( <SetupNotice isSetupError={ false } /> );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'should show default message for unset error', () => {
		const { getByText } = render( <SetupNotice isSetupError={ true } /> );
		expect(
			getByText(
				'Unable to set up the plugin. Refresh the page and try again.'
			)
		).toHaveClass( 'wc-admin-shipping-banner-install-error' );
	} );

	it( 'should show download message for download error', () => {
		const { getByText } = render(
			<SetupNotice
				isSetupError={ true }
				errorReason={ setupErrorTypes.DOWNLOAD }
			/>
		);
		expect(
			getByText(
				'Unable to download the plugin. Refresh the page and try again.'
			)
		).toHaveClass( 'wc-admin-shipping-banner-install-error' );
	} );
} );
