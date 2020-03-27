/**
 * External dependencies
 */
import { mount } from 'enzyme';

/**
 * Internal dependencies
 */
import SetupNotice, { setupErrorTypes } from '../';

describe( 'SetupNotice', () => {
	it( 'should be hidden for no error', () => {
		const notice = mount( <SetupNotice isSetupError={ false } /> );
		expect( notice.isEmptyRender() ).toBe( true );
	} );

	it( 'should show div if there is an error', () => {
		const notice = mount( <SetupNotice isSetupError={ true } /> );
		const contents = notice.find(
			'.wc-admin-shipping-banner-install-error'
		);
		expect( contents.length ).toBe( 1 );
	} );

	it( 'should show download message for download error', () => {
		const notice = mount(
			<SetupNotice
				isSetupError={ true }
				errorReason={ setupErrorTypes.DOWNLOAD }
			/>
		);
		expect(
			notice.text().includes( 'Unable to download the plugin' )
		).toBe( true );
	} );

	it( 'should show default message for unset error', () => {
		const notice = mount( <SetupNotice isSetupError={ true } /> );
		expect( notice.text().includes( 'Unable to set up the plugin' ) ).toBe(
			true
		);
	} );
} );
