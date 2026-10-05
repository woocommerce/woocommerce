import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Header } from '../index';
vi.mock( '@woocommerce/settings', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/settings' ) ),
		getSetting() {
			return 'Fake Site Title';
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const encodedBreadcrumb = [
	[ 'admin.php?page=wc-settings', 'Settings' ],
	'Accounts &amp; Privacy',
];
describe( 'Header', () => {
	it( 'should render decoded breadcrumb name', () => {
		const { queryByText } = render(
			<Header sections={ encodedBreadcrumb } isEmbedded={ true } />
		);
		expect( queryByText( 'Accounts &amp; Privacy' ) ).toBe( null );
		expect( queryByText( 'Accounts & Privacy' ) ).not.toBe( null );
	} );
} );
