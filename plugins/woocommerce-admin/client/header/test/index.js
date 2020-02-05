/**
 * External dependencies
 *
 * @format
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import Header from '../index.js';

const encodedBreadcrumb = [
	[ 'admin.php?page=wc-settings', 'Settings' ],
	'Accounts &amp; Privacy',
];

describe( 'Header', () => {
	test( 'should render decoded breadcrumb name', () => {
		const header = shallow( <Header sections={ encodedBreadcrumb } isEmbedded={ true } />, {
			disableLifecycleMethods: true,
		} );
		expect( header.text().includes( 'Accounts &amp; Privacy' ) ).toBe( false );
		expect( header.text().includes( 'Accounts & Privacy' ) ).toBe( true );
	} );
} );
